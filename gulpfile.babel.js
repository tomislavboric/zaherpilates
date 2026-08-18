'use strict';

import plugins       from 'gulp-load-plugins';
import yargs         from 'yargs';
import browser       from 'browser-sync';
import gulp          from 'gulp';
import rimraf        from 'rimraf';
import yaml          from 'js-yaml';
import fs            from 'fs';
import path          from 'path';
import dateFormat    from 'dateformat';
import webpackStream from 'webpack-stream';
import webpack2      from 'webpack';
import named         from 'vinyl-named';
import log           from 'fancy-log';
import colors        from 'ansi-colors';

var dartSass = require('gulp-sass')(require('sass'));

// Load all Gulp plugins into one variable
const $ = plugins();

// Check for --production flag
const PRODUCTION = !!(yargs.argv.production);

// Check for --development flag unminified with sourcemaps
const DEV = !!(yargs.argv.dev);

// Load settings from settings.yml
const { BROWSERSYNC, REVISIONING, PATHS } = loadConfig();

// Source and destination folders for images
const IMAGES_SRC  = 'src/assets/images';
const IMAGES_DEST = PATHS.dist + '/assets/images';

// Check if file exists synchronously
function checkFileExists(filepath) {
  let flag = true;
  try {
    fs.accessSync(filepath, fs.F_OK);
  } catch(e) {
    flag = false;
  }
  return flag;
}

// Load default or custom YML config file
function loadConfig() {
  log('Loading config file...');

  if (checkFileExists('config.yml')) {
    // config.yml exists, load it
    log(colors.bold(colors.cyan('config.yml')), 'exists, loading', colors.bold(colors.cyan('config.yml')));
    let ymlFile = fs.readFileSync('config.yml', 'utf8');
    return yaml.load(ymlFile);

  } else if(checkFileExists('config-default.yml')) {
    // config-default.yml exists, load it
    log(colors.bold(colors.cyan('config.yml')), 'does not exist, loading', colors.bold(colors.cyan('config-default.yml')));
    let ymlFile = fs.readFileSync('config-default.yml', 'utf8');
    return yaml.load(ymlFile);

  } else {
    // Exit if config.yml & config-default.yml do not exist
    log('Exiting process, no config file exists.');
    log('Error Code:', err.code);
    process.exit(1);
  }
}

// Delete the "dist" folder, except the generated images
// The images folder is kept so images can be processed incrementally
// (see the "images" and "prune-images" tasks below)
function clean(done) {
  // Everything in "dist" that is not the "assets" folder
  rimraf(PATHS.dist + '/!(assets)', () => {
    // Everything in "dist/assets" that is not the "images" folder
    rimraf(PATHS.dist + '/assets/!(images)', done);
  });
}

// Copy files out of the assets folder
// This task skips over the "images", "js", and "scss" folders, which are parsed separately
function copy() {
  // encoding: false keeps binary assets intact under gulp 5 (vinyl-fs 4)
  return gulp.src(PATHS.assets, { encoding: false })
    .pipe(gulp.dest(PATHS.dist + '/assets'));
}

// Compile Sass into CSS
// In production, the CSS is compressed
function sass() {
  return gulp.src([
    'src/assets/scss/app.scss',
    'src/assets/scss/editor.scss'
  ])
    .pipe($.sourcemaps.init())
    .pipe(dartSass.sync({
      includePaths: PATHS.sass
    })
      .on('error', dartSass.logError))
    .pipe($.autoprefixer())

    .pipe($.if(PRODUCTION, $.cleanCss({ compatibility: 'ie9' })))
    .pipe($.if(!PRODUCTION, $.sourcemaps.write()))
    .pipe($.if(REVISIONING && PRODUCTION || REVISIONING && DEV, $.rev()))
    .pipe(gulp.dest(PATHS.dist + '/assets/css'))
    .pipe($.if(REVISIONING && PRODUCTION || REVISIONING && DEV, $.rev.manifest()))
    .pipe(gulp.dest(PATHS.dist + '/assets/css'))
    .pipe(browser.reload({ stream: true }));
}

// Combine JavaScript into one file
// In production, the file is minified
const webpack = {
  config: {
    mode: PRODUCTION ? 'production' : 'development',
    devtool: PRODUCTION ? false : 'inline-source-map',
    module: {
      rules: [
        {
          test: /.js$/,
          loader: 'babel-loader',
          exclude: /node_modules(?![\\\/]foundation-sites)/,
        },
      ],
    },
    externals: {
      jquery: 'jQuery',
    },
  },

  changeHandler(err, stats) {
    log('[webpack]', stats.toString({
      colors: true,
    }));

    browser.reload();
  },

  build() {
    return gulp.src(PATHS.entries)
      .pipe(named())
      .pipe(webpackStream(webpack.config, webpack2))
      .pipe($.if(PRODUCTION, $.uglify()
        .on('error', e => { console.log(e); }),
      ))
      .pipe($.if(REVISIONING && PRODUCTION || REVISIONING && DEV, $.rev()))
      .pipe(gulp.dest(PATHS.dist + '/assets/js'))
      .pipe($.if(REVISIONING && PRODUCTION || REVISIONING && DEV, $.rev.manifest()))
      .pipe(gulp.dest(PATHS.dist + '/assets/js'));
  },

  watch() {
    const watchConfig = Object.assign({}, webpack.config, {
      watch: true,
      devtool: 'inline-source-map',
    });

    return gulp.src(PATHS.entries)
      .pipe(named())
      .pipe(webpackStream(watchConfig, webpack2, webpack.changeHandler)
        .on('error', (err) => {
          log('[webpack:error]', err.toString({
            colors: true,
          }));
        }),
      )
      .pipe(gulp.dest(PATHS.dist + '/assets/js'));
  },
};

gulp.task('webpack:build', webpack.build);
gulp.task('webpack:watch', webpack.watch);

// List all files inside a folder, recursively, as paths relative to that folder
// Dotfiles (.DS_Store etc.) are skipped, just like gulp.src does by default
function listFiles(dir, base = dir, found = []) {
  if (!checkFileExists(dir)) {
    return found;
  }

  fs.readdirSync(dir, { withFileTypes: true }).forEach(entry => {
    if (entry.name.startsWith('.')) {
      return;
    }

    const full = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      listFiles(full, base, found);
    } else {
      found.push(path.relative(base, full));
    }
  });

  return found;
}

// Source images that are missing in "dist" or newer than their processed copy
function outdatedImages() {
  return listFiles(IMAGES_SRC).filter(file => {
    const source = fs.statSync(path.join(IMAGES_SRC, file));
    let dest;

    try {
      dest = fs.statSync(path.join(IMAGES_DEST, file));
    } catch (e) {
      // Not built yet
      return true;
    }

    return source.mtimeMs > dest.mtimeMs;
  });
}

// Delete images in "dist" that no longer exist in "src", plus any empty folders
function pruneImages(done) {
  let removed = 0;

  listFiles(IMAGES_DEST).forEach(file => {
    if (!checkFileExists(path.join(IMAGES_SRC, file))) {
      fs.unlinkSync(path.join(IMAGES_DEST, file));
      log('Image ' + colors.bold(colors.magenta(file)) + ' was removed from dist.');
      removed++;
    }
  });

  removeEmptyFolders(IMAGES_DEST);

  if (!removed) {
    log('Images: nothing to remove.');
  }

  done();
}

// Recursively remove folders that are left empty after pruning
function removeEmptyFolders(dir) {
  if (!checkFileExists(dir)) {
    return;
  }

  fs.readdirSync(dir, { withFileTypes: true }).forEach(entry => {
    if (entry.isDirectory()) {
      removeEmptyFolders(path.join(dir, entry.name));
    }
  });

  if (dir !== IMAGES_DEST && fs.readdirSync(dir).length === 0) {
    fs.rmdirSync(dir);
  }
}

// Copy images to the "dist" folder
// Only new or changed images are processed
// Images are copied as-is: optimise them at the source instead
function images(done) {
  const outdated = outdatedImages();

  if (!outdated.length) {
    log('Images: all up to date, nothing to process.');
    return done();
  }

  log('Images: processing ' + colors.bold(colors.cyan(outdated.length)) + ' new or changed file(s).');

  // encoding: false keeps binary assets intact under gulp 5 (vinyl-fs 4)
  return gulp.src(outdated.map(file => path.join(IMAGES_SRC, file)), { base: IMAGES_SRC, encoding: false })
    .pipe(gulp.dest(IMAGES_DEST));
}

// Images tasks
gulp.task('prune-images', pruneImages);
gulp.task('images', gulp.series('prune-images', images));

// Create a .zip archive of the theme
function archive() {
  var time = dateFormat(new Date(), "yyyy-mm-dd_HH-MM");
  var pkg = JSON.parse(fs.readFileSync('./package.json'));
  var title = pkg.name + '_' + time + '.zip';

  // encoding: false keeps binary assets intact under gulp 5 (vinyl-fs 4)
  return gulp.src(PATHS.package, { encoding: false })
    .pipe($.zip(title))
    .pipe(gulp.dest('packaged'));
}

// PHP Code Sniffer task
gulp.task('phpcs', function() {
  return gulp.src(PATHS.phpcs)
    .pipe($.phpcs({
      bin: 'wpcs/vendor/bin/phpcs',
      standard: './codesniffer.ruleset.xml',
      showSniffCode: true,
    }))
    .pipe($.phpcs.reporter('log'));
});

// Start BrowserSync to preview the site in
function server(done) {
  browser.init({
    proxy: BROWSERSYNC.url,

    ui: {
      port: 8080
    },

  });
  done();
}

// Reload the browser with BrowserSync
function reload(done) {
  browser.reload();
  done();
}

// Watch for changes to static assets, pages, Sass, and JavaScript
function watch() {
  gulp.watch(PATHS.assets, copy);
  gulp.watch('src/assets/scss/**/*.scss', sass)
    .on('change', path => log('File ' + colors.bold(colors.magenta(path)) + ' changed.'))
    .on('unlink', path => log('File ' + colors.bold(colors.magenta(path)) + ' was removed.'));
  gulp.watch('**/*.php', reload)
    .on('change', path => log('File ' + colors.bold(colors.magenta(path)) + ' changed.'))
    .on('unlink', path => log('File ' + colors.bold(colors.magenta(path)) + ' was removed.'));
  gulp.watch(IMAGES_SRC + '/**/*', gulp.series('images', reload))
    .on('change', path => log('File ' + colors.bold(colors.magenta(path)) + ' changed.'))
    .on('unlink', path => log('File ' + colors.bold(colors.magenta(path)) + ' was removed.'));
}

// Build the "dist" folder by running all of the below tasks
gulp.task('build',
  gulp.series(clean, gulp.parallel(sass, 'webpack:build', 'images', copy)));

// Build the site, run the server, and watch for file changes
gulp.task('default',
  gulp.series('build', server, gulp.parallel('webpack:watch', watch)));

// Package task
gulp.task('package',
  gulp.series('build', archive));

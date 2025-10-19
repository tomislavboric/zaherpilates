# FoundationPress

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/olefredrik/foundationpress?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)
[![GitHub version](https://badge.fury.io/gh/olefredrik%2Ffoundationpress.svg)](https://github.com/olefredrik/FoundationPress/releases)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

This is a starter-theme for WordPress based on Zurb's [Foundation for Sites 6](https://foundation.zurb.com/sites.html), the most advanced responsive (mobile-first) framework in the world. The purpose of FoundationPress, is to act as a small and handy toolbox that contains the essentials needed to build any design. FoundationPress is meant to be a starting point, not the final product.

Please fork, copy, modify, delete, share or do whatever you like with this.

All contributions are welcome!

## Requirements

**This project requires [Node.js](http://nodejs.org) v6.x.x 11.6.x to be installed on your machine.** Please be aware that you might encounter problems with the installation if you are using the most current Node version (bleeding edge) with all the latest features.

FoundationPress uses [Sass](http://Sass-lang.com/) (CSS with superpowers). In short, Sass is a CSS pre-processor that allows you to write styles more effectively and tidy.

The Sass is compiled using libsass, which requires the GCC to be installed on your machine. Windows users can install it through [MinGW](http://www.mingw.org/), and Mac users can install it through the [Xcode Command-line Tools](http://osxdaily.com/2014/02/12/install-command-line-tools-mac-os-x/).

If you have not worked with a Sass-based workflow before, I would recommend reading [FoundationPress for beginners](https://foundationpress.olefredrik.com/posts/tutorials/foundationpress-for-beginners), a short blog post that explains what you need to know.

## Quickstart

### 1. Clone the repository and install with npm
```bash
$ cd my-wordpress-folder/wp-content/themes/
$ git clone https://github.com/olefredrik/FoundationPress.git
$ cd FoundationPress
$ npm install
```

### 2. Configuration

#### YAML config file
FoundationPress includes a `config-default.yml` file. To make changes to the configuration, make a copy of `config-default.yml` and name it `config.yml` and make changes to that file. The `config.yml` file is ignored by git so that each environment can use a different configuration with the same git repo.

At the start of the build process a check is done to see if a `config.yml` file exists. If `config.yml` exists, the configuration will be loaded from `config.yml`. If `config.yml` does not exist, `config-default.yml` will be used as a fallback.

#### Browsersync setup
If you want to take advantage of [Browsersync](https://www.browsersync.io/) (automatic browser refresh when a file is saved), simply open your `config.yml` file after creating it in the previous step, and put your local dev-server address and port (e.g http://localhost:8888) in the `url` property under the `BROWSERSYNC` object.

#### Static asset hashing / cache breaker
If you want to make sure your users see the latest changes after re-deploying your theme, you can enable static asset hashing. In your `config.yml`, set ``REVISIONING: true;``. Hashing will work on the ``npm run build`` and ``npm run dev`` commands. It will not be applied on the start command with browsersync. This is by design.  Hashing will only change if there are actual changes in the files.

### 3. Get started

```bash
$ npm start
```

### 4. Compile assets for production

When building for production, the CSS and JS will be minified. To minify the assets in your `/dist` folder, run

```bash
$ npm run build
```

#### To create a .zip file of your theme, run:

```
$ npm run package
```

Running this command will build and minify the theme's assets and place a .zip archive of the theme in the `packaged` directory. This excludes the developer files/directories from your theme like `/node_modules`, `/src`, etc. to keep the theme lightweight for transferring the theme to a staging or production server.

### Project structure

In the `/src` folder you will find the working files for all your assets. Every time you make a change to a file that is watched by Gulp, the output will be saved to the `/dist` folder. The contents of this folder is the compiled code that you should not touch (unless you have a good reason for it).

The `/page-templates` folder contains templates that can be selected in the Pages section of the WordPress admin panel. To create a new page-template, simply create a new file in this folder and make sure to give it a template name.

I guess the rest is quite self explanatory. Feel free to ask if you feel stuck.

### Styles and Sass Compilation

 * `style.css`: Do not worry about this file. (For some reason) it's required by WordPress. All styling are handled in the Sass files described below

 * `src/assets/scss/app.scss`: Make imports for all your styles here
 * `src/assets/scss/global/*.scss`: Global settings
 * `src/assets/scss/components/*.scss`: Buttons etc.
 * `src/assets/scss/modules/*.scss`: Topbar, footer etc.
 * `src/assets/scss/templates/*.scss`: Page template styling

 * `dist/assets/css/app.css`: This file is loaded in the `<head>` section of your document, and contains the compiled styles for your project.

If you're new to Sass, please note that you need to have Gulp running in the background (``npm start``), for any changes in your scss files to be compiled to `app.css`.

### JavaScript Compilation

All JavaScript files, including Foundation's modules, are imported through the `src/assets/js/app.js` file. The files are imported using module dependency with [webpack](https://webpack.js.org/) as the module bundler.

If you're unfamiliar with modules and module bundling, check out [this resource for node style require/exports](http://openmymind.net/2012/2/3/Node-Require-and-Exports/) and [this resource to understand ES6 modules](http://exploringjs.com/es6/ch_modules.html).

Foundation modules are loaded in the `src/assets/js/app.js` file. By default all components are loaded. You can also pick and choose which modules to include. Just follow the instructions in the file.

If you need to output additional JavaScript files separate from `app.js`, do the following:
* Create new `custom.js` file in `src/assets/js/`. If you will be using jQuery, add `import $ from 'jquery';` at the top of the file.
* In `config.yml`, add `src/assets/js/custom.js` to `PATHS.entries`.
* Build (`npm start`)
* You will now have a `custom.js` file outputted to the `dist/assets/js/` directory.

## Demo

* [Clean FoundationPress install](http://foundationpress.olefredrik.com/)
* [FoundationPress Kitchen Sink - see every single element in action](http://foundationpress.olefredrik.com/kitchen-sink/)

## Local Development
We recommend using one of the following setups for local WordPress development:

* [MAMP](https://www.mamp.info/en/) (macOS)
* [WAMP](http://www.wampserver.com/en/download-wampserver-64bits/) (Windows)
* [LAMP](https://www.linux.com/learn/easy-lamp-server-installation) (Linux)
* [Local](https://local.getflywheel.com/) (macOS/Windows)
* [VVV (Varying Vagrant Vagrants)](https://github.com/Varying-Vagrant-Vagrants/VVV) (Vagrant Box)
* [Trellis](https://roots.io/trellis/)


## Tutorials

* [FoundationPress for beginners](https://foundationpress.olefredrik.com/posts/tutorials/foundationpress-for-beginners/)
* [Responsive images in WordPress with Interchange](http://rachievee.com/responsive-images-in-wordpress/)
* [Learn to use the _settings file to change almost every aspect of a Foundation site](http://zurb.com/university/lessons/66)
* [Other lessons from Zurb University](http://zurb.com/university/past-lessons)

## Documentation

* [Zurb Foundation Docs](http://foundation.zurb.com/docs/)
* [WordPress Codex](http://codex.wordpress.org/)

## Showcase

* [Harvard Center for Green Buildings and Cities](http://www.harvardcgbc.org/)
* [The New Tropic](http://thenewtropic.com/)
* [Parent-Child Home Program](http://www.parent-child.org/)
* [Hip and Healthy](http://hipandhealthy.com/)
* [Franchise Career Advisors](http://franchisecareeradvisors.com/)
* [Maren Schmidt](http://marenschmidt.com/)
* [Finnerodja](http://www.finnerodja.se/)
* [WP Diamonds](http://www.wpdiamonds.com/)
* [Storm Arts](http://stormarts.fi/)
* [ProfitGym](http://profitgym.nl/)
* [Agritur Piasina](http://www.agriturpiasina.it/)
* [Byington Vineyard & Winery](https://byington.com/)
* [Show And Tell](http://www.showandtelluk.com/)
* [Wahl + Case](https://www.wahlandcase.com/)
* [Morgridge Institute for Research](https://morgridge.org)
* [Impeach Trump Now](https://impeachdonaldtrumpnow.org/)
* [La revanche des sites](https://www.la-revanche-des-sites.fr/)
* [Dyami Wilson](https://dyamiwilson.com)
* [Madico Solutions](https://madico.com)

>Credit goes to all the brilliant designers and developers out there. Have **you** made a site that should be on this list? [Please let me know](https://twitter.com/olefredrik)

## Contributing
#### Here are ways to get involved:

1. [Star](https://github.com/olefredrik/FoundationPress/stargazers) the project!
2. Answer questions that come through [GitHub issues](https://github.com/olefredrik/FoundationPress/issues)
3. Report a bug that you find
4. Share a theme you've built on top of FoundationPress
5. [Tweet](https://twitter.com/intent/tweet?original_referer=http%3A%2F%2Ffoundationpress.olefredrik.com%2F&text=Check%20out%20FoundationPress%2C%20the%20ultimate%20%23WordPress%20starter-theme%20built%20on%20%23Foundation%206&tw_p=tweetbutton&url=http%3A%2F%2Ffoundationpress.olefredrik.com&via=olefredrik) and [blog](http://www.justinfriebel.com/my-first-experience-with-foundationpress-a-wordpress-starter-theme-106/) your experience of FoundationPress.

#### Pull Requests

Pull requests are highly appreciated. Please follow these guidelines:

1. Solve a problem. Features are great, but even better is cleaning-up and fixing issues in the code that you discover
2. Make sure that your code is bug-free and does not introduce new bugs
3. Create a [pull request](https://help.github.com/articles/creating-a-pull-request)
4. Verify that all the Travis-CI build checks have passed


# Comments System - Complete Package

## 📦 Files Included

1. **comments.php** - WordPress comments template
2. **comments-styles.scss** - Complete styling (compile to CSS)
3. **functions.php** - AJAX handler code (add to your theme's functions.php)

## ✨ Features

### 1. **Reply to Comments**
- Click "Odgovori" on any comment
- Form moves under the comment with special styling
- Different title: "Odgovorite korisniku [Name]"
- Shorter placeholder: "Napišite svoj odgovor..."
- Button changes to: "Objavi odgovor"
- Cancel link: "← Odustani od odgovora"

### 2. **Edit Comments**
- "Uredi" button appears only for comment author
- Inline editing with textarea
- Save or cancel changes
- Success message appears after saving
- AJAX save - no page reload

### 3. **Emoji Picker**
- 3 categories of emojis
- Easy insertion into comments
- Brand-colored hover effects

### 4. **Nested Comments**
- Up to 3 levels deep
- Subtle background and left border for replies
- "Odgovor za [Name]" indicator
- Proper indentation (52px desktop, 32px mobile)

### 5. **Brand Styling**
- Rose/terracotta color scheme (#c79288)
- Clean, modern design
- Fully responsive
- Smooth transitions and animations

## 🚀 Installation

### Step 1: Add Comments Template
Replace your theme's `comments.php` with the provided file.

### Step 2: Add Styles
Add the SCSS to your theme's styles and compile, or convert to CSS and enqueue.

```php
// In functions.php
wp_enqueue_style('comments-style', get_template_directory_uri() . '/css/comments.css');
```

### Step 3: Add AJAX Handler
Copy the entire function from `functions.php` artifact and paste it into your theme's `functions.php` file.

### Step 4: Ensure Comment Reply Script
Make sure WordPress comment reply script is loaded:

```php
// In functions.php
if (is_singular() && comments_open() && get_option('thread_comments')) {
    wp_enqueue_script('comment-reply');
}
```

## 📱 Responsive Design

- **Desktop**: Full 52px indentation for nested comments
- **Mobile**: Reduced 32px indentation
- Edit form buttons stack vertically on mobile
- Smaller avatars on mobile (36px vs 40px)

## 🔒 Security Features

- WordPress nonce verification
- User ownership check (can only edit own comments)
- Content sanitization
- Optional time limit for editing (commented out)

## 🎨 Customization

### Colors
Change these variables in SCSS:
```scss
$brand-primary: #c79288;    // Main brand color
$brand-secondary: #d4a296;  // Secondary accent
```

### Time Limit for Editing
To enable 30-minute edit limit, uncomment these lines in `functions.php`:
```php
if ((time() - $comment_time) > $time_limit) {
    wp_send_json_error('Komentar se može uređivati samo 30 minuta nakon objavljivanja');
    return;
}
```

### Nesting Depth
Change `max_depth` in comments.php:
```php
'max_depth' => 3,  // Change to 2, 4, or 5
```

## 🐛 Troubleshooting

**Edit button not appearing:**
- Make sure you're logged in and viewing your own comment
- Check that `get_current_user_id()` works on your site

**AJAX not working:**
- Verify functions.php code is added correctly
- Check browser console for JavaScript errors
- Ensure admin-ajax.php is accessible

**Styles not applying:**
- Compile SCSS to CSS if needed
- Check CSS is properly enqueued
- Clear browser cache

## 📝 Croatian Translations

All text is in Croatian:
- "Uredi" = Edit
- "Spremi" = Save
- "Odustani" = Cancel
- "Odgovori" = Reply
- "Odgovor za" = Reply to
- Success/error messages in Croatian

## 💡 Tips

1. Test editing on a staging site first
2. Backup your comments before deployment
3. Consider adding edit history tracking
4. Monitor for spam/abuse with edit feature
5. Use caching plugin? Make sure AJAX works

## 🎯 Browser Support

- ✅ Chrome/Edge (modern)
- ✅ Firefox (modern)
- ✅ Safari (modern)
- ✅ Mobile browsers (iOS/Android)

## 📄 License

Use freely in your projects. No attribution required.

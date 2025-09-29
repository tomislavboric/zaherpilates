<?php
// Version 5 layout - matching your current implementation
if ( post_password_required() ) return;
?>

<div class="comments-section">

	<header class="comments-header">
		<h2 class="comments-title">Vaši dojmovi</h2>
		<p class="comments-subtitle">Podijelite kako ste se osjećali nakon vježbanja</p>
	</header>

	<div class="encouragement-banner">
		💫 Vaša iskustva inspiriraju ostale žene - svaki komentar je dragocjen!
	</div>

	<div class="comments-stats">
		<span class="stat-item">
			<strong><?php echo get_comments_number(); ?></strong> komentara
		</span>
		<span class="stat-item">
			<strong>98%</strong> pozitivnih
		</span>
		<span class="stat-item">
			<strong>4.9</strong> ⭐ ocjena
		</span>
	</div>

	<div class="motivation-prompt">
		💭 Kako ste se osjećali tijekom vježbanja? Koja vam je vježba bila najizazovnija? Što ste naučile novo o svom tijelu danas?
	</div>

	<?php if ( is_user_logged_in() ) :

		// Clean form setup
		add_filter('comment_form_default_fields', fn() => []);
		add_filter('comment_form_defaults', function($defaults) {
			$defaults['comment_notes_before'] = '';
			$defaults['comment_notes_after'] = '';
			$defaults['logged_in_as'] = '';
			return $defaults;
		});

		comment_form([
			'title_reply' => 'Podijelite svoj dojam',
			'title_reply_before' => '<h3 class="comment-form-title">',
			'title_reply_after' => '</h3>',
			'class_form' => 'comment-form-v5',
			'class_submit' => 'comment-submit-btn',
			'label_submit' => 'Objavi komentar',
			'comment_field' => '
				<div class="comment-form-field">
					<textarea
						id="comment"
						name="comment"
						class="comment-textarea"
						rows="4"
						required
						placeholder="Recite nam kako vam je bilo... osjećate li se snažnije? Možda ste napravile neku vježbu bolje nego prije? Svaki napredak je vrijedan slavlja!"
					></textarea>
					<div class="emoji-picker">
						<button type="button" class="emoji-toggle">😊</button>
						<div class="emoji-panel" id="emojiPanel">
							<div class="emoji-category">
								<span class="emoji-category-title">Osjećaji</span>
								<div class="emoji-grid">
									<button type="button" class="emoji-btn" onclick="addEmoji(\'😊\')">😊</button>
									<button type="button" class="emoji-btn" onclick="addEmoji(\'😍\')">😍</button>
									<button type="button" class="emoji-btn" onclick="addEmoji(\'🥰\')">🥰</button>
									<button type="button" class="emoji-btn" onclick="addEmoji(\'😌\')">😌</button>
									<button type="button" class="emoji-btn" onclick="addEmoji(\'🤗\')">🤗</button>
									<button type="button" class="emoji-btn" onclick="addEmoji(\'😇\')">😇</button>
								</div>
							</div>
							<div class="emoji-category">
								<span class="emoji-category-title">Pilates</span>
								<div class="emoji-grid">
									<button type="button" class="emoji-btn" onclick="addEmoji(\'💪\')">💪</button>
									<button type="button" class="emoji-btn" onclick="addEmoji(\'🧘‍♀️\')">🧘‍♀️</button>
									<button type="button" class="emoji-btn" onclick="addEmoji(\'✨\')">✨</button>
									<button type="button" class="emoji-btn" onclick="addEmoji(\'🌟\')">🌟</button>
									<button type="button" class="emoji-btn" onclick="addEmoji(\'💚\')">💚</button>
									<button type="button" class="emoji-btn" onclick="addEmoji(\'🔥\')">🔥</button>
								</div>
							</div>
							<div class="emoji-category">
								<span class="emoji-category-title">Posebno</span>
								<div class="emoji-grid">
									<button type="button" class="emoji-btn" onclick="addEmoji(\'🎉\')">🎉</button>
									<button type="button" class="emoji-btn" onclick="addEmoji(\'👏\')">👏</button>
									<button type="button" class="emoji-btn" onclick="addEmoji(\'🙌\')">🙌</button>
									<button type="button" class="emoji-btn" onclick="addEmoji(\'💖\')">💖</button>
									<button type="button" class="emoji-btn" onclick="addEmoji(\'🌸\')">🌸</button>
									<button type="button" class="emoji-btn" onclick="addEmoji(\'🌺\')">🌺</button>
								</div>
							</div>
						</div>
					</div>
				</div>',
			'submit_button' => '<button type="submit" class="%3$s">%4$s</button>',
		]);

	endif; ?>

	<?php if ( have_comments() ) : ?>

		<div class="comments-list">
			<?php
			wp_list_comments([
				'style' => 'div',
				'short_ping' => true,
				'avatar_size' => 50,
				'callback' => function($comment, $args, $depth) {
					$GLOBALS['comment'] = $comment; ?>

					<div class="comment-item" id="comment-<?php comment_ID(); ?>">
						<div class="comment-avatar">
							<?php
							$avatar = get_avatar($comment, 40);
							if ($avatar) {
								echo $avatar;
							} else {
								$initial = strtoupper(substr(get_comment_author(), 0, 1));
								echo '<div class="avatar-initial">' . $initial . '</div>';
							}
							?>
						</div>

						<div class="comment-content">
							<div class="comment-meta">
								<h4 class="comment-author"><?php echo get_comment_author(); ?></h4>
								<span class="comment-date">
									<?php echo esc_html(hr_time_ago_for_comment($comment->comment_ID, 30)); ?>
								</span>
							</div>

							<div class="comment-text">
								<?php comment_text(); ?>
							</div>

							<?php /*
							 <div class="comment-actions">
								<button class="like-button" onclick="toggleLike(this)">
									👍 <span class="like-count"><?php echo rand(1, 15); ?></span>
								</button>
							</div> */ ?>

						</div>
					</div>

				<?php },
			]);
			?>
		</div>

		<?php if ( get_comment_pages_count() > 1 && get_option('page_comments') ) : ?>
			<nav class="comments-pagination">
				<?php paginate_comments_links(); ?>
			</nav>
		<?php endif; ?>

	<?php endif; ?>

</div>

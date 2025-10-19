<?php
// Version 5 layout - improved reply structure with edit functionality
// IMPORTANT: You need to add the edit_user_comment AJAX handler to your functions.php
// See the separate functions.php artifact for the code
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
			'title_reply_to' => 'Odgovorite korisniku %s',
			'title_reply_before' => '<h3 class="comment-form-title">',
			'title_reply_after' => '</h3>',
			'cancel_reply_before' => '<div class="cancel-reply-wrapper">',
			'cancel_reply_after' => '</div>',
			'cancel_reply_link' => '← Odustani od odgovora',
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
						data-placeholder-main="Recite nam kako vam je bilo... osjećate li se snažnije? Možda ste napravile neku vježbu bolje nego prije? Svaki napredak je vrijedan slavlja!"
						data-placeholder-reply="Napišite svoj odgovor..."
						placeholder="Recite nam kako vam je bilo... osjećate li se snažnije? Možda ste napravile neku vježbu bolje nego prije? Svaki napredak je vrijedan slavlja!"
					></textarea>
					<div class="emoji-picker">
						<button type="button" class="emoji-toggle">😊</button>
						<div class="emoji-panel" id="emojiPanel">
							<div class="emoji-category">
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
			'submit_field' => '<div class="form-submit">%1$s %2$s</div>',
			'submit_button' => '<button type="submit" class="%3$s" data-label-main="Objavi komentar" data-label-reply="Objavi odgovor">%4$s</button>',
		]);

	endif; ?>

	<?php if ( have_comments() ) : ?>

		<div class="comments-list">
			<?php
			wp_list_comments([
				'style' => 'ol',
				'short_ping' => true,
				'avatar_size' => 40,
				'max_depth' => 3,
				'callback' => function($comment, $args, $depth) {
					$GLOBALS['comment'] = $comment;
					$is_reply = ($comment->comment_parent != 0);
					?>

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
							<?php
							// Show reply indicator if this is a nested comment
							if ($is_reply) {
								$parent_comment = get_comment($comment->comment_parent);
								if ($parent_comment) {
									echo '<div class="comment-reply-to">';
									echo '<span class="reply-arrow">↩</span>';
									echo '<span>Odgovor za <strong>' . esc_html(get_comment_author($parent_comment)) . '</strong></span>';
									echo '</div>';
								}
							}
							?>

							<div class="comment-meta">
								<h4 class="comment-author"><?php echo get_comment_author(); ?></h4>
								<span class="comment-date">
									<?php echo esc_html(hr_time_ago_for_comment($comment->comment_ID, 30)); ?>
								</span>
							</div>

							<div class="comment-text">
								<?php comment_text(); ?>
							</div>

							<div class="comment-actions">
								<?php
								// Edit button - only show to comment author
								if ($comment->comment_approved == '1' && get_current_user_id() == $comment->user_id) {
									echo '<button class="edit-comment-btn" data-comment-id="' . $comment->comment_ID . '">Uredi</button>';
								}

								// Reply button - only show if NOT your own comment
								if ($comment->comment_approved == '1' && get_current_user_id() != $comment->user_id) {
									comment_reply_link(array_merge($args, [
										'add_below' => 'comment',
										'depth' => $depth,
										'max_depth' => $args['max_depth'],
										'reply_text' => 'Odgovori',
										'before' => '<button class="reply-button">',
										'after' => '</button>'
									]));
								}
								?>
							</div>

						</div>
					</div>

				<?php }
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

<script>
// Handle reply form text changes
document.addEventListener('DOMContentLoaded', function() {
	const textarea = document.getElementById('comment');
	const submitBtn = document.querySelector('.comment-submit-btn');

	if (!textarea || !submitBtn) return;

	// Function to update form text based on reply state
	function updateFormText() {
		const commentParentInput = document.getElementById('comment_parent');
		const isReply = commentParentInput && commentParentInput.value !== '0';

		if (isReply) {
			// Reply mode
			textarea.placeholder = textarea.getAttribute('data-placeholder-reply') || 'Napišite svoj odgovor...';
			textarea.rows = 3;
			submitBtn.textContent = submitBtn.getAttribute('data-label-reply') || 'Objavi odgovor';
		} else {
			// Main comment mode
			textarea.placeholder = textarea.getAttribute('data-placeholder-main') || 'Recite nam kako vam je bilo...';
			textarea.rows = 4;
			submitBtn.textContent = submitBtn.getAttribute('data-label-main') || 'Objavi komentar';
		}
	}

	// Watch for reply button clicks
	document.addEventListener('click', function(e) {
		if (e.target.closest('.reply-button')) {
			setTimeout(updateFormText, 100);
		}

		if (e.target.closest('.cancel-comment-reply-link')) {
			setTimeout(updateFormText, 100);
		}
	});

	updateFormText();
});

// Handle comment editing
document.addEventListener('DOMContentLoaded', function() {
	document.addEventListener('click', function(e) {
		// Edit button clicked
		if (e.target.classList.contains('edit-comment-btn')) {
			const commentId = e.target.getAttribute('data-comment-id');
			const commentItem = document.getElementById('comment-' + commentId);
			const commentTextDiv = commentItem.querySelector('.comment-text');
			const commentActions = commentItem.querySelector('.comment-actions');

			// Get current comment text (strip <p> tags)
			const currentText = commentTextDiv.querySelector('p')
				? commentTextDiv.querySelector('p').textContent
				: commentTextDiv.textContent;

			// Create edit form
			const editForm = document.createElement('div');
			editForm.className = 'comment-edit-form';
			editForm.innerHTML = `
				<textarea class="comment-edit-textarea" rows="3">${currentText.trim()}</textarea>
				<div class="comment-edit-actions">
					<button class="save-edit-btn" data-comment-id="${commentId}">Spremi</button>
					<button class="cancel-edit-btn">Odustani</button>
				</div>
			`;

			// Hide original text and actions
			commentTextDiv.style.display = 'none';
			commentActions.style.display = 'none';

			// Insert edit form
			commentTextDiv.parentNode.insertBefore(editForm, commentTextDiv.nextSibling);

			// Focus textarea
			editForm.querySelector('.comment-edit-textarea').focus();
		}

		// Cancel edit button clicked
		if (e.target.classList.contains('cancel-edit-btn')) {
			const editForm = e.target.closest('.comment-edit-form');
			const commentItem = editForm.closest('.comment-item');
			const commentTextDiv = commentItem.querySelector('.comment-text');
			const commentActions = commentItem.querySelector('.comment-actions');

			// Show original text and actions
			commentTextDiv.style.display = '';
			commentActions.style.display = '';

			// Remove edit form
			editForm.remove();
		}

		// Save edit button clicked
		if (e.target.classList.contains('save-edit-btn')) {
			const saveBtn = e.target;
			const commentId = saveBtn.getAttribute('data-comment-id');
			const editForm = saveBtn.closest('.comment-edit-form');
			const textarea = editForm.querySelector('.comment-edit-textarea');
			const newContent = textarea.value.trim();

			if (!newContent) {
				alert('Komentar ne može biti prazan');
				return;
			}

			// Disable button during save
			saveBtn.disabled = true;
			saveBtn.textContent = 'Spremam...';

			// Send AJAX request
			fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'edit_user_comment',
					comment_id: commentId,
					content: newContent,
					nonce: '<?php echo wp_create_nonce('edit_comment_nonce'); ?>'
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					// Update comment text
					const commentItem = document.getElementById('comment-' + commentId);
					const commentTextDiv = commentItem.querySelector('.comment-text');
					const commentActions = commentItem.querySelector('.comment-actions');

					commentTextDiv.innerHTML = '<p>' + data.data.content + '</p>';
					commentTextDiv.style.display = '';
					commentActions.style.display = '';

					// Remove edit form
					editForm.remove();

					// Show success message briefly
					const successMsg = document.createElement('div');
					successMsg.className = 'comment-edit-success';
					successMsg.textContent = '✓ Komentar uspješno spremljen';
					commentTextDiv.parentNode.insertBefore(successMsg, commentTextDiv);

					setTimeout(() => successMsg.remove(), 3000);
				} else {
					alert('Greška: ' + (data.data || 'Nepoznata greška'));
					saveBtn.disabled = false;
					saveBtn.textContent = 'Spremi';
				}
			})
			.catch(error => {
				console.error('Error:', error);
				alert('Došlo je do greške prilikom spremanja');
				saveBtn.disabled = false;
				saveBtn.textContent = 'Spremi';
			});
		}
	});
});
</script>

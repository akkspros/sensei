<?php
/**
 * The Template for displaying Multiple Choice Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz/question_type-multiple-choice.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.3.0
 */

global $post, $woothemes_sensei, $current_user;
// Get Frontend Data
$user_quizzes = $woothemes_sensei->frontend->data->user_quizzes;
$question_item = $woothemes_sensei->frontend->data->question_item;
$question_count = $woothemes_sensei->frontend->data->question_count;
$quiz_passmark = $woothemes_sensei->frontend->data->quiz_passmark;
$user_quiz_grade = $woothemes_sensei->frontend->data->user_quiz_grade;
$lesson_complete = $woothemes_sensei->frontend->data->user_lesson_complete;
$reset_quiz_allowed = $woothemes_sensei->frontend->data->reset_quiz_allowed;
$quiz_grade_type = $woothemes_sensei->frontend->data->quiz_grade_type;

// Question ID
$question_id = $question_item->ID;

// Question answers
$question_right_answer = get_post_meta( $question_id, '_question_right_answer', true );
$question_wrong_answers = get_post_meta( $question_id, '_question_wrong_answers', true );

// Question media
$question_media = get_post_meta( $question_id, '_question_media', true );
$question_media_type = $question_media_thumb = $question_media_link = $question_media_title = $question_media_description = '';
if( 0 < intval( $question_media ) ) {
	$mimetype = get_post_mime_type( $question_media );
	if( $mimetype ) {
		$mimetype_array = explode( '/', $mimetype);
		if( isset( $mimetype_array[0] ) && $mimetype_array[0] ) {
			$question_media_type = $mimetype_array[0];
			$question_media_url = wp_get_attachment_url( $question_media );
			$attachment = get_post( $question_media );
			$question_media_title = $attachment->post_title;
			$question_media_description = $attachment->post_content;
			switch( $question_media_type ) {
				case 'image':
					$image_size = apply_filters( 'sensei_question_image_size', 'medium', $question_id );
					$attachment_src = wp_get_attachment_image_src( $question_media, $image_size );
					$question_media_link = '<a class="' . esc_attr( $question_media_type ) . '" title="' . esc_attr( $question_media_title ) . '" href="' . esc_url( $question_media_url ) . '" target="_blank"><img src="' . $attachment_src[0] . '" width="' . $attachment_src[1] . '" height="' . $attachment_src[2] . '" /></a>';
				break;

				case 'audio':
					$question_media_link = wp_audio_shortcode( array( 'src' => $question_media_url ) );
				break;

				case 'video':
					$question_media_link = wp_video_shortcode( array( 'src' => $question_media_url ) );
				break;

				default:
					$question_media_filename = basename( $question_media_url );
					$question_media_link = '<a class="' . esc_attr( $question_media_type ) . '" title="' . esc_attr( $question_media_title ) . '" href="' . esc_url( $question_media_url ) . '" target="_blank">' . $question_media_filename . '</a>';
				break;
			}
		}
	}
}

// Merge right and wrong answers
array_push( $question_wrong_answers, $question_right_answer );

// Setup answer array
foreach( $question_wrong_answers as $answer ) {
	$answer_id = WooThemes_Sensei_Lesson::get_answer_id( $answer );
	$question_answers[ $answer_id ] = $answer;
}

$answers_sorted = array();
$random_order = get_post_meta( $question_id, '_random_order', true );
if( ! $random_order || ( $random_order && $random_order == 'yes' ) ) {
	$answers_sorted = $question_answers;
	shuffle( $answers_sorted );
} else {
	$answer_order = array();
	$answer_order_string = get_post_meta( $question_id, '_answer_order', true );
	if( $answer_order_string ) {
		$answer_order = array_filter( explode( ',', $answer_order_string ) );
		if( count( $answer_order ) > 0 ) {
			foreach( $answer_order as $answer_id ) {
				if( $question_answers[ $answer_id ] ) {
					$answers_sorted[ $answer_id ] = $question_answers[ $answer_id ];
					unset( $question_answers[ $answer_id ] );
				}
			}

			if( count( $question_answers ) > 0 ) {
				foreach( $question_answers as $id => $answer ) {
					$answers_sorted[ $id ] = $answer;
				}
			}
		}
	} else {
		$answers_sorted = $question_answers;
		shuffle( $answers_sorted );
	}
}

$question_grade = get_post_meta( $question_id, '_question_grade', true );
if( ! $question_grade || $question_grade == '' ) {
	$question_grade = 1;
}
$user_answer_entry = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $question_id, 'user_id' => $current_user->ID, 'type' => 'sensei_user_answer' ), true );
$user_question_grade = WooThemes_Sensei_Utils::sensei_get_user_question_grade( $user_answer_entry );

$question_text = $question_item->post_title;
$question_description = apply_filters( 'the_content', $question_item->post_content, $question_item->ID );

$answer_message = false;
$answer_notes = false;
if( ( $lesson_complete && $user_quiz_grade != '' ) || ( $lesson_complete && ! $reset_quiz_allowed && 'auto' == $quiz_grade_type ) || ( 'auto' == $quiz_grade_type && ! $reset_quiz_allowed && $user_quiz_grade != '' ) ) {
	$user_correct = false;
	$answer_message = __( 'Incorrect', 'woothemes-sensei' );
	$answer_message_class = 'user_wrong';
	if( $user_question_grade > 0 ) {
		$user_correct = true;
		$answer_message = sprintf( __( 'Grade: %d', 'woothemes-sensei' ), $user_question_grade );
		$answer_message_class = 'user_right';
	}
	$answer_notes = WooThemes_Sensei_Utils::sensei_get_user_question_answer_notes( $user_answer_entry );
	if( $answer_notes ) {
		$answer_message_class .= ' has_notes';
	}
}

?>
<li class="multiple-choice">
	<span><?php echo esc_html( stripslashes( $question_text ) ); ?> <span>[<?php echo $question_grade; ?>]</span></span>
	<?php echo $question_description; ?>
	<?php if( $question_media_link ) { ?>
		<div class="question_media_display">
			<?php echo $question_media_link; ?>
			<dl>
				<?php if( $question_media_title ) { ?>
					<dt><?php echo $question_media_title; ?></dt>
				<?php } ?>
				<?php if( $question_media_description ) { ?>
					<?php echo '<dd>' . $question_media_description . '</dd>'; ?>
				<?php } ?>
			</dl>
		</div>
	<?php } ?>
	<?php if( $answer_message ) { ?>
		<div class="answer_message <?php esc_attr_e( $answer_message_class ); ?>">
			<span><?php echo $answer_message; ?></span>
		</div>
	<?php } ?>
	<input type="hidden" name="<?php echo esc_attr( 'question_id_' . $question_id ); ?>" value="<?php echo esc_attr( $question_id ); ?>" />
	<ul>
	<?php $count = 0; ?>
	<?php foreach( $answers_sorted as $id => $question ) {
		$checked = '';
		$count++;

		$answer_class = '';
		if( isset( $user_correct ) ) {
			if( $user_quizzes[ $question_id ] == $question ) {
				$answer_class = 'user_wrong';
				if( $user_correct ) {
					$answer_class = 'user_right';
				}
			}
			if( $question_right_answer == $question ) {
				$answer_class .= ' right_answer';
			}
		}

		if ( isset( $user_quizzes[ $question_id ] ) && ( '' != $user_quizzes[ $question_id ] ) ) {
			if( 0 == get_magic_quotes_gpc() ) {
				$user_answer = stripslashes( $user_quizzes[ $question_id ] );
			} else {
				$user_answer = $user_quizzes[ $question_id ];
			}
			$checked = checked( $question, $user_answer, false );
		} // End If Statement ?>
		<li class="<?php esc_attr_e( $answer_class ); ?>">
			<input type="radio" id="<?php echo esc_attr( 'question_' . $question_id ) . '-option-' . $count; ?>" name="<?php echo esc_attr( 'sensei_question[' . $question_id . ']' ); ?>" value="<?php echo esc_attr( stripslashes( $question ) ); ?>" <?php echo $checked; ?><?php if ( !is_user_logged_in() ) { echo ' disabled'; } ?>>&nbsp;
			<label for="<?php echo esc_attr( 'question_' . $question_id ) . '-option-' . $count; ?>"><?php echo esc_html( stripslashes( $question ) ); ?></label>
		</li>
	<?php } // End For Loop ?>
	</ul>
	<?php if( $answer_notes ) { ?>
		<div class="sensei-message info info-special"><?php echo $answer_notes; ?></div>
	<?php } ?>
</li>
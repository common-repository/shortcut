<?php get_header(); ?>

	<div id="content" class="narrowcolumn">
		<h2>ERROR!</h2>
		<?php echo "Shortcut Error: ".$error; // $error returns a description of the error. ?>
		<br>
		<br>
		<br>
		<h2>The 5 Most Recent Shortcuts.</h2>
		<?php get_recent_shortcuts(); ?>
	</div>

<?php get_footer(); ?>

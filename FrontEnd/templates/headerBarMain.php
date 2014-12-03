<div class="series-title">
	<div class="header-caption"><?php echo $header;?></div>
	<div class="header-buttons-right">
		<a href="javascript:void(0)" id="setup-box-link">Setup</a>
		<br class="clear">
	</div>
	<br class="clear">
	<ul id="setup-menu">
		<li><a href="install">Install</a></li>
		<li>
			<form method="post" action="movies/update">
				<button type="submit">Update Movies</button>
			</form>
		</li>
		<li>
			<form method="post" action="shows/update">
				<button type="submit">Update Shows</button>
			</form>
		</li>
	</ul>
</div>
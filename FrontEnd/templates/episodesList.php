<div id='episodes-outer-wrapper' style='background-image: url("<?php echo $imageUrl;?>")'> 
	<div class='episodes-wrapper'>
		<?php if (count($showData) > 1){?>
			<ul class="episodes-navigation">
			<?php foreach ($showData as $season){?>
				<li>
					<a href="<?php echo "#".$season["title"];?>-link"><?php echo $season["title"];?></a>
				</li>
			<?php }?>
			</ul>
		<?php }?>
		<ul class='series-list'>
		<?php foreach ($showData as $season){?>
			<li>
			<span class='season-title' id="<?php echo $season["title"];?>-link"><?php echo $season["title"];?></span>
			<ul class='episode-list'>
			<?php foreach($season["episodes"] as $episode){?>
				<?php 
				$class = "episode-link-missing";
				$link = "";
				if (!empty($episode["link"])){
					$class = "episode-link-present";
					$link = $episode["link"];
				}
				?>
				<li class="<?php echo $class;?>">
					<a href='javascript:void(0)' title='<?php echo $episode["label"];?>' 
						data-id='<?php echo $episode["id"];?>' data-href='<?php echo $link;?>'>
						<?php echo $episode["label"];?>
					</a>
				</li>
			<?php }?>
			</ul>
			</li>
		<?php }?>
		</ul>
	</div>
	<div id='episode-details'>
	</div>
</div>
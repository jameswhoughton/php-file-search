<?php
ini_set('display_errors','1');
session_start();
if(isset($_GET['logout'])) {
	unset($_SESSION['session_id']);
	unset($_GET['logout']);
}
?>
<html>
	<head>
		<title>PHP File Search</title>
		<link rel="stylesheet" href="assets/style.css" />
	</head>
	<body>
		<header>
			<h1>File Search</h1>
		</header>
		<div id="content">
		<?php if(!isset($_SESSION['session_id'])) { ?>
			<form action="lib/auth.php" method="POST">
				<label>Password</label>
				<input type="password" name="password"></input>
				<input type="submit"></input>
			</form>
		<?php } else { ?>
			<a href="index.php?logout=1">Logout</a>
			<form action="search.php" method="POST">
				<div class="row">
					<div class="form-element">
						<label>Search</label>
						<input type="text" name="search"></input>
					</div>
					<div class="form-element">
						<label>Directory</label>
						<?php
							function buildTree($dir) {
								chdir($dir);
								$dir_list = glob('*');
								$out = array();

								foreach($dir_list as $element) {
									if(is_dir($element)) {
										$out[$element] = buildTree($element);
									}
								}
								chdir('../');
								return $out;
							}

							function printList($dir, $parent) {
								echo '<ul>';
								foreach($dir as $key => $e) {

									echo '<li>';
									if(sizeof($e)) {
										echo '<span class="angle-down">&#10148;</span>';
										echo '<span data-val="'. $parent .'/'. $key .'" class="name">'. $key .'</span>';
										echo printList($e, $parent .'/'. $key);
									} else {
										echo '<span data-val="'. $parent .'/'. $key .'" class="name">'. $key .'</span>';;
									}
									echo '</li>';
								}
								echo '</ul>';
							}
							$pwd = getcwd();
							chdir('../');
							$dir_tree = buildTree('.');
							chdir($pwd);
							//print_r($dir_tree);
						?>
						<div id="directory">
							<span id="selected">.</span>
							<span class="angle-down">&#10148;</span>
						</div>
						<input type="hidden" name="dir" value="." />
						<div id="dir-list">
							<?php
							printList($dir_tree, '.');
							?>
						</div>
					</div>
					<div class="form-element">
						<a id="submit">Search</a>
					</div>
					<div class="form-element">
						<a id="filters">Exclusions</a>
					</div>
					<div class="filter-block">
						<div class="form-element">
							<label>Name (accepts regex)</label>
							<input type="text" name="name" />
						</div>
						<div class="form-element">
							<label>Extension (e.g. jpg,txt,doc)</label>
							<input type="text" name="extension" />
						</div>
					</div>
				</div>
			</form>
			<div id="results"></div>
			<script type="text/javascript">
			var dir_arrows = document.querySelectorAll('#dir-list .angle-down');
			var dirs = document.querySelectorAll('#dir-list .name');

			for(n = 0; n < dir_arrows.length; n++) {
				dir_arrows[n].addEventListener('click', function(e) {
					var sub_list = this.nextSibling.nextSibling;

					var current_state = sub_list.style.display;
					if(current_state == 'block') {
						sub_list.style.display = 'none';
						this.style.transform = 'rotate(0deg)';
					} else {
						sub_list.style.display = 'block';
						this.style.transform = 'rotate(90deg)';
					}
				})
			}

			for(n = 0; n < dirs.length; n++) {
				dirs[n].addEventListener('click', function(e) {
					var value = this.dataset.val;
					var text = this.innerHTML;

					document.getElementById('selected').innerHTML = text;
					document.querySelector('input[name=dir]').value = value;

					document.getElementById('dir-list').style.display = 'none';
					document.querySelector('#directory .angle-down').style.transform = 'rotate(90deg)';
				})
			}

			document.getElementById('directory').onclick = function() {
				var sub_list = document.getElementById('dir-list');
				var angle = document.querySelector('#directory .angle-down');
				var current_state = sub_list.style.display;
				if(current_state == 'block') {
					sub_list.style.display = 'none';
					angle.style.transform = 'rotate(90deg)';
				} else {
					sub_list.style.display = 'block';
					angle.style.transform = 'rotate(-90deg)';
				}
			};

			document.getElementById('submit').onclick = function() {

				var form = document.querySelector('#content form');
				var form_data = new FormData(form);
				var results_div = document.getElementById('results');

				var xhr = new XMLHttpRequest();
				xhr.open('POST', 'lib/search.php');
				results_div.innerHTML = 'Loading...';

				xhr.onreadystatechange = function() {
					if (xhr.readyState==4 && xhr.status==200) {
						var results = JSON.parse(xhr.responseText);

						if(results['refresh']) {
							location.reload()
						}

						var html = '';
						if(results['results']) {
							var result = results['results'];
							html += '<div id="count">' + results['count'] + ' results</div>';
							for(var i = 0; i < result.length; i++) {
								html +='<div class="result">';
								html += '<div class="result-title">' + result[i]['file'] + '</div>';
								html += '<table class="result-detail">';
								html += '<thead><tr><td>Line</td><td>Content</td></tr></thead><tbody>';
								for(var j = 0; j < result[i]['result'].length; j++) {
									html += '<tr><td>' + result[i]['result'][j]['line_number'] + '</td>';
									html += '<td>' + result[i]['result'][j]['line'] + '</td></tr>';
								}
								html += '</tbody></table>';
								html += '</div>';
							}
						} else {
							html = 'No Results';
						}
						results_div.innerHTML = html;
					}
				}
				xhr.send(form_data);
			}

			document.getElementById('filters').onclick = function() {
				document.getElementsByName('name')[0].value = '';
				document.getElementsByName('extension')[0].value = '';

				var filter_block = document.getElementsByClassName('filter-block')[0];

				elementToggle(filter_block);
			}

			document.addEventListener('click', function(event) {
				var element = event.target;
				if(element.classList.contains('result-title')) {
					var table = element.nextSibling;

					elementToggle(table);
				}
			})

			var elementToggle = function(element) {
				var current_state = element.offsetParent;

				if(current_state === null) {
					element.style.display = 'block';
				} else {
					element.style.display = 'none';
				}
			}
			</script>
		<?php } ?>
		</div>
	</body>
</html>
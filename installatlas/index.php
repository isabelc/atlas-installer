<?php
/**
 * ZodiacPress Custom Atlas Installer
 *
 * "ZodiacPress Custom Atlas Installer" is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 * 
 * "ZodiacPress Custom Atlas Installer" is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with "ZodiacPress Custom Atlas Installer". If not, see <http://www.gnu.org/licenses/>.
 *
 * @author     Isabel Castillo
 * @copyright  2018 Isabel Castillo
 * @version    1.1
 * @license    https://www.gnu.org/licenses/gpl-2.0.html  GNU GPLv2
 */

ini_set('display_errors',1);// @test
require_once 'helper-db.php';
$complete = '<span class="okay">&#x2713; Done</span>';
?>
<!DOCTYPE html>
<html>
<head>
	<title>ZodiacPress Custom Atlas Installer</title>
	
	<script>

	function startWorking(){
		wd=window.setInterval('workingDots()',800);
	}
	function stopWorking(){
		window.clearInterval(wd);
	}
	function workingDots(){
		var elW = document.getElementById("working");
		if (typeof i === 'undefined') i = 0;
		i = ++i % 4;
		elW.innerText = "Working "+Array(i+1).join(".");
	}
	function notice(msg,status){
		var el = document.getElementById("notices");
	    status = status || "success";
		elChild = document.createElement("div");
		elChild.className = status + " notice-text";
		elChild.innerText = msg;
		el.appendChild(elChild);
		scrollTo(document.body, 0, 100);
	}

	/* Replace button with "Done" */
	function hideButton(button){
		if (typeof button === 'undefined')
			return;

		var td = document.getElementById(button.id + "-control");
		elChild = document.createElement("span");
		elChild.className = "okay";
		elChild.innerText = '\u2713 Done';
		button.remove();
		td.appendChild(elChild);
	}

	window.onload = function(){

		/****************************************************
		*
		* BEGIN buttons
		*
		****************************************************/
		
		var elW = document.getElementById("working");
		elW.style.visibility = 'hidden';	
		 
		var cdButtons = [].slice.call(document.getElementsByClassName("cd-button"));
		if (cdButtons.length > 0) {

			cdButtons.forEach(function (button){
			  button.addEventListener("click", function(e){

			  	e.preventDefault();
				button.disabled = true;
		    	var bspinner = document.getElementById(button.id + "-spinner");
	      		bspinner.classList.add("spinner");			
				// remove prior notice
				var notices = document.getElementsByClassName("notice-text");
				if (notices.length > 0) {
					notices[0].remove();
				}
		    	startWorking();
		    	elW.style.visibility = 'visible';

			    if (button.id.startsWith('import-')) {

			    	// Download Buttons

	    		    // get filename from button
		    		var aiFilename = button.value;

		    		/****************************************************
		    		* 
		    		* BEGIN 1st ajax request
		    		* 
		    		****************************************************/

					var xhr = new XMLHttpRequest();
					xhr.open('GET', 'ajax-download.php?f=' + aiFilename);
					xhr.onload = function() {
						elW.style.visibility = 'hidden';
						button.disabled = false;
						bspinner.classList.remove("spinner");
						stopWorking();
					    if (xhr.status === 200) {
					    	
							var r = JSON.parse(xhr.responseText);

					    	if ('working' == r.status) {

					    		// kick off another request to retry download.

					    		/****************************************************
					    		* 
					    		* BEGIN nested ajax request (Request #2)
					    		* 
					    		****************************************************/
					    		button.disabled = true;
					    		bspinner.classList.add("spinner");
								startWorking();
				    			elW.style.visibility = 'visible';
								var xhr2 = new XMLHttpRequest();
								xhr2.open('GET','ajax-download.php?f=' + aiFilename + '&retry=1');
								xhr2.onload = function() {
									elW.style.visibility = 'hidden';
									button.disabled = false;
									bspinner.classList.remove("spinner");
									stopWorking();

									if (xhr2.status === 200) {
										var r2 = JSON.parse(xhr2.responseText);
										notice(r2.message,r2.status);
										if ('success' == r2.status || 'info' == r2.status) {
											hideButton(button);
										}
										
									} else {
					        			notice('Request #2 failed. Returned status of ' + xhr2.status, 'error');
					    			}
								};
								xhr2.send();

					    		/****************************************************
					    		* 
					    		* END nested ajax request (Request #2)
					    		* 
					    		****************************************************/

					    	} else {
					    		notice(r.message,r.status);
								if ('success' == r.status || 'info' == r.status) {
									hideButton(button);
								}

					    	}

					        
					    } else {
					        notice('Request failed. Returned status of ' + xhr.status, 'error');
					    }
					};
					xhr.send();


		    		/****************************************************
		    		* 
		    		* END 1st ajax request
		    		* 
		    		****************************************************/



			    } // END Download Buttons

		    	else {
			    	token = document.getElementById('token');

					var xhr = new XMLHttpRequest();
					xhr.open('GET', 'ajax-' + button.id + '.php?v=' + button.value + '&t=' + token.value);
				    	
					xhr.onload = function() {
						elW.style.visibility = 'hidden';
						button.disabled = false;
						bspinner.classList.remove("spinner");
						stopWorking();
					    if (xhr.status === 200) {
					    	var r = JSON.parse(xhr.responseText);
				    		notice(r.message,r.status);
							if ('success' == r.status || 'info' == r.status) {
								hideButton(button);
							}
					    } else {
					        notice('Request failed. Returned status of ' + xhr.status, 'error');
					    }
					};
					xhr.send();

				} // end all other buttons

			  },false);

			}); // end foreach
		
		} // end if (cdButtons.length > 0)

		/****************************************************
		*
		* END buttons
		*
		****************************************************/

	};


	</script>


	<style>
		body {
			color: #030205;
			background:#fefdf3;
			font-family: "Lucida Grande", "Lucida Sans", Verdana, sans-serif;
			font-size: 14px;
			line-height: 1.618
		}

		#main {
		    width: 650px;
		    max-width: 100%;
		    margin-left: auto;
		    margin-right: auto;
		}
		#zp-atlas-installer table {
		  text-align: left;
		  line-height: 40px;
		  border-collapse: separate;
		  border-spacing: 0;
		  border: 2px solid #5F4B8B;
		  margin: 20px auto 50px auto;
		  border-radius: .25rem;
		}

		#zp-atlas-installer thead tr:first-child {
		  background: #5F4B8B;
		  color: #fff;
		  border: none;
			
		}
		#zp-atlas-installer th:first-child {
			padding: 0 10px;
		}
		 
		#zp-atlas-installer td {
		  padding: 8px 15px 8px 20px;
		}

		#zp-atlas-installer tbody td {
		  border-bottom: 1px solid #ddd;
		}
		#zp-atlas-installer tbody tr:last-child td {
		  border: none;
		}
		#zp-atlas-installer td:last-child {
		  padding-left: 6px;
		line-height: 1.618;
		}

		#notices div {
			padding: 14px;
			margin: 0 0 14px;
			width: 100%;
			box-sizing: border-box;
		}
		#notices .success{
		    border-top: 1px solid #62c528;
		    color: #4F8A10;
		/* 	color:#fff; */
		/* 	color: #0DA020; */
		    background-color: #DFF2BF;
		/* 	background: #0DA020; */
		}
		#notices .error {
			background-color: #e24e4e;
			color: #fff;
		}
		#notices .info {
			border-top: 1px solid #00529B;
			color: #00529B;
			background-color: #BDE5F8;
		}
		#working {
			padding: 14px;
			margin: 0 0 14px;
			color: #9F6000;
			background-color: #FEEFB3;
			border-top: 1px solid #fddf67;
		}
		.okay {
			color: #008000;
			white-space: nowrap;
		}

		code {

			background: #EDEAF4;
		    border: 1px solid #ddd;
		    color: #666;
			 color:#222;
		    font-family: monospace;
		    padding: 1px 2px;
		}
		.loading-spinner{
		  position:relative;
		  display:block;
		}
		@keyframes spinner {
		  to {transform: rotate(360deg);}
		}
		.spinner {
		    min-height:36px;
		}
		.spinner:before {
		  content: '';
		  box-sizing: border-box;
		  position: absolute;
		  top: 50%;
		  left: 50%;
		  width: 20px;
		  height: 20px;
		  margin-top: -10px;
		  margin-left: -10px;
		  border-radius: 50%;
		  border: 2px solid #ccc;
		  border-top-color: #333;
		  animation: spinner 1s linear infinite;
		}
		#dbinstall-control input {
		  max-width: 145px;
		  margin-bottom:8px;
		}
		#dbinstall-control {
		  line-height: 1.3;
		}

	</style>
</head>
<body>
<div id="main">
<h1>ZodiacPress Custom Atlas Installer</h1>
<p>This tool will help you install the ZodiacPress Atlas database in a separate database rather than in your WordPress database. </p>

<div id="working"></div>
<div id="notices"></div>

<form id="zp-atlas-installer">

	<table>
		<thead>
			<tr>
				<th colspan="3">Complete steps in order</th>
			</tr>
		</thead>
		<tbody>

			<tr>
				<td>
					<?php
					//if custom db has been set, show 'Done'
					if (zpai_are_db_details_set()) {
						echo $complete;
					} else {
						echo '<br>';
					}
					?>
				</td>

				<td>
					<span>
						<strong>Step 1:</strong> Edit the <strong>helper-db.php</strong> file on lines 13-16 to set your own database name, database username, password, and database host.
					</span>
				</td>
			</tr>

			<tr>
				<?php
				$basename = 'cities';
				$filename = 'cities.txt';
				?>
				<td id="import-<?php echo $basename; ?>-control">
					<?php
					// if file exists, and is the right size, show 'Done' instead of button.
					$file = sys_get_temp_dir() . '/' . $filename;

					/****************************************************
					* @todo update Current filesize of cities.txt
					****************************************************/
					if (file_exists($file) && filesize($file) === 275665461) {
						echo $complete;
					} else {
						?>
						<button id="import-<?php echo $basename; ?>" class="cd-button" value="<?php echo $filename; ?>">Download Data File  <span id="import-<?php echo $basename; ?>-spinner" class="loading-spinner"></span></button>
						<?php
					}
					?>
				</td>
				<td>
					<span id="import-<?php echo $basename; ?>label"><strong>Step 2:</strong> Download the <code><?php echo $filename; ?></code> data file from Cosmic Plugins Data Export Server.  <mark>NOTE: this can take up to 2 minutes to complete.</mark></span>
				</td>
			</tr>

			<tr>
				<td id="dbinstall-control"><input type="hidden" id="token" value="<?php echo crypt('nucHd73ksd73kdfIyd7Ykd0235d','xtdHnckcnd8f$Ds87Axichdn3'); ?>" />

					<?php
					if (zpai_is_atlas_installed()) {
						echo $complete;
					} else {
						?>

						<button id="dbinstall" class="cd-button" name="dbinstall" value="dbinstall">Install Atlas <span id="dbinstall-spinner" class="loading-spinner"></span></button>

						<?php
					}
					?>
				</td>
				<td>
					<span id="dbinstall-label"><strong>Step 3:</strong> Install the atlas by importing all the cities data into your database. <mark>NOTE: This can take about 3 minutes because this database will be about 321 MB in size, holding 3.4 million records! That is one for each city, town, or village.</mark></span>
				</td>
			</tr>

		</tbody>

	</table>
	
</form>


</div>

</body>
</html>

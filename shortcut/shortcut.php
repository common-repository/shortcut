<?php
/*
Plugin Name: shortcut
Plugin URI: http://www.goreki.com/plugins/shortcut
Description: Create unique short URL's from your domain to any of your pages (eg. http://domain.com/_shortcut)
Version: 0.7.0
Author: Paul Taylor
Author URI: http://www.goreki.com
License: GPL2

*/


$lu_db_version = "1.0";
$shortcut['prefix'] = get_option('shortcut_prefix');
$shortcut['trim'] = get_option('shortcut_trim');
$shortcut['folder'] = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
$shortcut['custom_ext'] = get_option('shortcut_custom_mask');
$shortcut['length'] = get_option('shortcut_hash_length');

function shortcut_custom_boxes() {
	/** remove_meta_box('postcustom','post','normal'); **/
	add_meta_box('shortcut', __( 'Shortcut','shortcut'),'shortcut_post_box','post','advanced');
	add_meta_box('shortcut', __( 'Shortcut','shortcut'),'shortcut_post_box','page','advanced');
}

function get_shortcut($id) {
	global $wpdb, $post, $shortcut;
	if(!$id) $id = $post->ID;
	
	if($shortcut['trim'] == 'trim') {
		$siteurl = str_replace("www.", "", get_bloginfo('siteurl'));
	} else {
		$siteurl = get_bloginfo('siteurl');
	}
	
	if(shortcut_check_post($id)) {
		$table_name = $wpdb->prefix . "shortcut";
		$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE  `postid` = '".$id."'");
		if($check == true) {
			 $result = $siteurl."/".$shortcut['prefix']."".$check[0]->hash;
		}
	} else {
		$result = get_permalink($id);
	}
	
	return $result;
}

function get_recent_shortcuts($count = '5',$include = 'all',$show = 'all',$as = '') {
	global $wpdb, $shortcut;
	
	if(!$count)		$count		= '5';
	if(!$include)	$include	= 'all';
	if(!$show)		$show		= 'all';
	if(!$as)		$as			= '';

	$table_name = $wpdb->prefix . "shortcut";
	if($show == 'all') {
    	$list = $wpdb->get_results("SELECT * FROM `".$table_name."` ORDER BY `wp_shortcut` . `id` DESC LIMIT 0,".$count."");
	} elseif($show == 'wp') {
    	$list = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE `type` = 'wp' ORDER BY `wp_shortcut` . `id` DESC LIMIT 0,".$count."");
	} elseif($show == 'wpm') {
    	$list = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE `type` = 'wpm' ORDER BY `wp_shortcut` . `id` DESC LIMIT 0,".$count."");
	} elseif($show == 'ext') {
    	$list = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE `type` = 'ext' ORDER BY `wp_shortcut` . `id` DESC LIMIT 0,".$count."");
	} else {
    	$list = $wpdb->get_results("SELECT * FROM `".$table_name."` ORDER BY `wp_shortcut` . `id` DESC LIMIT 0,".$count."");
    }
    
    if($show != 'array') { 
    	echo "<style>td.break {word-break: break-word;}</style>\n";
    	echo "<table class=\"shortcut-table\">\n"; 
    }
    $recent_array = array();
    $count = '0';
    foreach($list as $item) {
    	if($as == 'array') {
    		if($shortcut['trim'] == 'trim') {
    			$siteurl = str_replace("www.", "", get_bloginfo('siteurl'))."/".$shortcut['prefix'];
    		} else {
    			$siteurl = get_bloginfo('siteurl')."/".$shortcut['prefix'];
    		}
    		
    		if(shortcut_check_masked($item->hash)) {
    			$extension	= ".".stripExtension($item->url);
    		} else {
    			$extension	= "";
    		}
    		
    		$recent_array[$count]['title']	= $item->title;
    		$recent_array[$count]['url']	= $item->url;
    		$recent_array[$count]['type']	= $item->type;
    		$recent_array[$count]['unique']	= $item->hash;
    		$recent_array[$count]['extension']	= $extension;
    		$recent_array[$count]['short']	= $siteurl.$item->hash.$extension;
    		$count++;
    	} else {
    		if($item->title) {
    			$title = $item->title;
    		} else {
    			$title = $item->url;
    		}
    		
    		if($item->type == 'wp') {
    			$url	= get_permalink($item->postid);
    			$title	= get_the_title($item->postid);
    		} elseif($item->type == 'wpm') {
    			$url	= get_post($item->postid);
    			$url	= $url->guid;
    			$title	= get_the_title($item->postid);
    		} elseif($item->type == 'ext') {
    			$url	= $item->url;
    			if($item->title) {
    				$title = $item->title;
    			} else {
    				$title = $item->url;
    			}
    		}
    		
    		if($shortcut['trim'] == 'trim') {
    			$siteurl = str_replace("www.", "", get_bloginfo('siteurl'))."/".$shortcut['prefix'];
    		} else {
    			$siteurl = get_bloginfo('siteurl')."/".$shortcut['prefix'];
    		}
    		
			if(shortcut_check_masked($item->hash)) {
				if($item->url) {
					$image = $item->url;
				} else {
					$image	= get_post($item->postid);
					$image	= $image->guid;
				}
				$extension	= ".".stripExtension($image);
				$note		= " <span class=\"description\"><i>(Masked)</i></span>";
			} else {
				$extension	= "";
				$note		= "";
			}
			
    		echo "<tr>\n";
    		if($include == 'url' || $include == 'all') {
    			echo "<td width=\"60%\" class=\"break shortcut-target\"><a href=\"".$url."\">".$title."</a></td>\n";
    		}
    		if($include == 'short' || $include == 'all') {
    			echo "<td width=\"40%\" class=\"shortcut-unique\">".$siteurl."<strong>".$item->hash.$extension."</strong></td>\n";
    		}
    		echo "</tr>\n";
    	}
    }
    if($show != 'array') {
    	echo "</table>\n";
    } else {
    	print_r($recent_array);
    }
}

function shortcut_goto() {
	global $wpdb, $shortcut;
	
	$count		= strlen($shortcut['prefix']);
	$path		= $_SERVER['REQUEST_URI'];
	$prefix		= substr($path, 1, $count);
	$path		= substr($path, 1);
	$unique		= substr($path, $count);
	$uniqueex	= explode(".", $unique);
	$unique		= $uniqueex[0];
	$extension	= $uniqueex[1];
	$custom_ext	= $shortcut['custom_ext'];
	
	if($prefix == $shortcut['prefix']) {
		if($extension) {
			if(shortcut_check_hash($unique)) {
			    if(shortcut_check_type($unique) == 'ext' || shortcut_check_type($unique) == 'wpm') {
			    	$table_name = $wpdb->prefix . "shortcut";
			    	$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE  `hash` = '".$unique."'");
			    	if(shortcut_check_type($unique) == 'ext') {
			    		$perma = $check[0]->url;
			    	} elseif(shortcut_check_type($unique) == 'wpm') {
			    		$perma = get_post($check[0]->postid);
			    		$perma = $perma->guid;
			    	}
			    }
				if(!is_user_logged_in()) {
				    shortcut_update_count($unique);
				}
				$url_ext = stripExtension($perma);	
				
				    $ext_type = array(
				    			'png'	=> 'image/png',
				    			'gif'	=> 'image/gif',
				    			'jpg'	=> 'image/jpeg',
				    			'jpeg'	=> 'image/jpeg',
				    			'js'	=> 'text/javascript',
				    			'pdf'	=> 'application/pdf',
				    			'txt'	=> 'text/plain',
				    			'mp3'	=> 'audio/mpeg'
				    			);		
				if($url_ext == 'zip') {
				    if(!shortcut_validate_external($perma)) {
				    	header('location: '.$perma.'');
				    } else {
				    	if(ini_get('zlib.output_compression')) {
				    		ini_set('zlib.output_compression', 'Off');
				    	}
				    	header('Pragma: public');						// required
				    	header('Expires: 0');							// no cache
				    	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				    	header('Cache-Control: private',false);
				    	header('Content-Type: application/zip');
				    	header('Content-Disposition: attachment; filename="'.$perma.'"');
				    	header('Content-Transfer-Encoding: binary');
				    	header('Content-Length: '.filesize($perma));	// provide file size
				    	header('Connection: close');
				    }
				} elseif($extension) {
				    if(stripExtension($perma) == $extension || $extension == $custom_ext) {
				    	header('Content-type: '.$ext_type[$url_ext].'');
				    	readfile($perma);
				    	die();
				    }
				} else {
				    header('location: '.$perma.'');
				    die();
				}
			
			} else {
				// error
			}
		} elseif(shortcut_check_masked($unique)) {
			// error
		} else {
			if(shortcut_check_hash($unique)) {
				if(shortcut_check_type($unique) == 'wp') {
					$postid = shortcut_get_postid($unique);
					$perma	= get_permalink($postid);
				} elseif(shortcut_check_type($unique) == 'wpm') {
					$postid = shortcut_get_postid($unique);
					$perma	= get_post($postid);
					$perma	= $perma->guid; 
				} elseif(shortcut_check_type($unique) == 'ext') {
					$table_name = $wpdb->prefix . "shortcut";
					$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE  `hash` = '".$unique."'");
					$perma = $check[0]->url;
				}
				if(!is_user_logged_in()) {
					shortcut_update_count($unique);
				}
				header('location: '.$perma.'');
				die();
			} else {
				// error
			}
		}
	}
}

function shortcut_errors() {
	global $wpdb, $shortcut;
	
	$count		= strlen($shortcut['prefix']);
	$path		= $_SERVER['REQUEST_URI'];
	$prefix		= substr($path, 1, $count);
	$path		= substr($path, 1);
	$unique		= substr($path, $count);
	$uniqueex	= explode(".", $unique);
	$unique		= $uniqueex[0];
	$extension	= $uniqueex[1];
	
	
	if(shortcut_check_hash($unique)) {
	    $table_name = $wpdb->prefix . "shortcut";
	    $check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE  `hash` = '".$unique."'");
	    $perma = $check[0]->url;
	}
		
	$file = 'shortcut_error.php';

	if($prefix == $shortcut['prefix']) {
		if(!shortcut_check_hash($unique)) {
			// Incorrect ID
			$error = "Incorrect ID.";
			$error_num	= "1";
			require_once($file);
			die();
		} elseif($extension) {
			if(stripExtension($perma) != $extension) {
				// Extensions Do Not Match
				$error = "Incorrect File Extension.";
				$error_num	= "2";
				require_once($file);
				die();
			}
		} elseif(!$extension) {
			if(shortcut_check_masked($unique)) {
				// Should be masked
				$error		= "No file extension.";
				$error_num	= "3";
				require_once($file);
				die();
			}
		}
	} elseif(!$prefix) {
		// Incorrect Prefix
		$error = "Incorrect Prefix.";
		$error_num	= "4";
		require_once($file);
		die();
	} else {
		require_once(TEMPLATEPATH . '/404.php');
	}
}

function shortcut_validate_external($url) {
	if(is_readable($url)) {
		return true;
	} else {
		return false;
	}
}

function stripExtension($filename = '') {
    if (!empty($filename)) {
        $filename = strtolower($filename); 
        $extArray = split("[/\\.]", $filename); 
        $p = count($extArray);
        $p = $p-1; 
        $extension = $extArray[$p]; 
        return $extension;
    } else {
        return false;
    }
}

function shortcut_check_masked($hash) {
	global $wpdb;
	$table_name = $wpdb->prefix . "shortcut";
	$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE  `hash` = '".$hash."'");
	if($check[0]->masked == true) {
		return true;
	} else {
		return false;
	}
}

function shortcut_can_mask($extension) {
    $ext_type = array('png','gif','jpg','jpeg','js','pdf','txt','mp3');
				    			
	if(in_array($extension, $ext_type)) {
		return true;
	} else {
		return false;
	}
}

function shortcut_post_box() {
	
	global $wpdb, $post, $shortcut;
	
	$table_name = $wpdb->prefix . "shortcut";
	$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE  `postid` = '".$_GET['post']."'");
	if($check == true) {
		$shortcut_unique = $check[0]->hash;
	} else {
		$shortcut_unique = shortcut_create_hash();
	}
	
	if($shortcut['trim'] == 'trim') {
		$siteurl = str_replace("www.", "", get_bloginfo('siteurl'))."/";
	} else {
		$siteurl = get_bloginfo('siteurl')."/";
	}
	
	$perma	 = get_permalink();

	$content .= '<p class="meta-options">';
	$content .= '<label for="no_title" class="selectit">' . __($siteurl.$shortcut['prefix']) . '</label>';
	$content .= '<input type="text" name="luhash" value="'.$shortcut_unique.'" size="20" />';
	$content .= '<input type="hidden" name="luhash_org" value="'.$shortcut_unique.'" />';
	$content .= '</p>';
	
	echo $content;
	
}

function shortcut_create_hash() {
	global $shortcut;
	$length = $shortcut['length'];
	$unique = uniqid();
	$unique = substr($unique, -$length);
	if(!shortcut_check_hash($unique)) {
		return $unique;
	} else {
		shortcut_create_hash();
	}
}

function shortcut_check_hash($hash) {
	global $wpdb;
	$table_name = $wpdb->prefix . "shortcut";
	$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE  `hash` = '".$hash."'");
	if($check == true) {
		return true;
	} else {
		return false;
	}
}

function shortcut_check_type($hash) {
	global $wpdb;
	$table_name = $wpdb->prefix . "shortcut";
	$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE  `hash` = '".$hash."'");
	if($check[0]->type == 'ext') {
		$type = 'ext';
		return $type;
	} elseif($check[0]->type == 'wp') {
		$type = 'wp';
		return $type;
	} elseif($check[0]->type == 'wpm') {
		$type = 'wpm';
		return $type;
	} else {
		$type = 'error';
		return $type;
	}
}

function shortcut_check_post($postid) {
	global $wpdb;
	$table_name = $wpdb->prefix . "shortcut";
	$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE  `postid` = '".$postid."'");
	if($check == true) {
		return true;
	} else {
		return false;
	}
}


function shortcut_check_url($url) {
	global $wpdb;
	$table_name = $wpdb->prefix . "shortcut";
	$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE  `url` = '".$url."' AND `type` = 'ext'");
	if($check == true) {
		return true;
	} else {
		return false;
	}
}

function shortcut_get_postid($hash) {
	global $wpdb;
	$table_name = $wpdb->prefix . "shortcut";
	$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE  `hash` = '".$hash."'");
	if($check == true) {
		return $check[0]->postid;
	}
}

function shortcut_update_count($hash) {
	global $wpdb;
	if(shortcut_check_hash($hash)) {
		$table_name = $wpdb->prefix . "shortcut";
		$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE `hash` = '".$hash."'");
		if($check == true) {
			$vcount = $check[0]->vcount;
			$vcount++;
			$insert = "UPDATE `".$table_name."` SET vcount = '".$vcount."' WHERE `hash` = '".$hash."'";
			$results = $wpdb->query($insert);
		}
	}
}

function shortcut_get_title($url) {
	$file = file($url);
	$file = implode("",$file);

	if(preg_match("/<title>(.+)<\/title>/i",$file,$title)) {
	    return $title[1];
	} else {
	    $notitle = '';
	    return $notitle;
	}
}


function shortcut_get_total($type,$get) {
	global $wpdb;
	$table_name = $wpdb->prefix . "shortcut";
	if($type == 'all') {
		if($get == 'rows') {
			$count = $wpdb->get_var("SELECT COUNT(*) FROM `".$table_name."`");
		} else {
			$check = $wpdb->get_results("SELECT * FROM `".$table_name."`");
		}
	} else {
		if($get == 'rows') {
			$count = $wpdb->get_var("SELECT COUNT(*) FROM `".$table_name."` WHERE `type` = '".$type."'");
		} else {
			$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE `type` = '".$type."'");
		}
	}
	if($get == 'total') {
		$vcount_array = array();
		foreach($check as $item) {
			array_push($vcount_array, $item->vcount);
		}
		return array_sum($vcount_array);
	} elseif($get == 'average') {
		$vcount_array = array();
		foreach($check as $item) {
			array_push($vcount_array, $item->vcount);
		}
		return array_sum($vcount_array) / count($vcount_array);
	} elseif($get == 'count') {
		return count($check);
	} elseif($get == 'rows') {
		return $count;
	}
}

function shortcut_publish_post($post_id) {
	global $wpdb, $post;

	if(shortcut_check_hash($_POST['luhash']) == true) {
		$hash = $_POST['luhash_org'];
	} else {
		$hash = $_POST['luhash'];
	}

	$table_name = $wpdb->prefix . "shortcut";
   
	if(!shortcut_check_post($post_id)) {
		$date	= date('Y-m-d H:i:s');
		
		$insert = "INSERT INTO " . $table_name .
            " (postid, hash, masked, vcount, type, date) " .
            "VALUES ('".$post_id."','".$hash."','','0','wp','".$date."')";

		$results = $wpdb->query($insert);
	} else {
		$table_name = $wpdb->prefix . "shortcut";
		$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE `postid` = '".$post_id."'");
		if($check == true) {
			$insert = "UPDATE `".$table_name."` SET hash = '".$hash."' WHERE `postid` = '".$post_id."'";
			$results = $wpdb->query($insert);
		}
	}
}

function shortcut_media_save() {
	global $wpdb;
	
    if(!shortcut_check_post($_POST['shortcut-unique'])) {	    	
    	$hash = $_POST['shortcut-unique'];
    } else {
    	$hash = $_POST['shortcut-unique-org'];
    }
    
	$image		= get_post($_POST['attachment_id']);
	$extension	= stripExtension($image->guid);
	
	if(shortcut_can_mask($extension)) {
		$mask = 'true';
	} else {
		$mask = '';
	}
    
    $table_name = $wpdb->prefix . "shortcut";
   
	if(!shortcut_check_post($_POST['attachment_id'])) {
		$date	= date('Y-m-d H:i:s');
		$insert = "INSERT INTO ".$table_name." (postid, hash, masked, vcount, type, date) VALUES ('".$_POST['attachment_id']."','".$hash."','".$mask."','0','wpm','".$date."')";
		$results = $wpdb->query($insert);
	} else {
		$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE `postid` = '".$_POST['attachment_id']."'");
		if($check == true) {
			$insert = "UPDATE `".$table_name."` SET `hash` = '".$hash."' WHERE `postid` = '".$_POST['attachment_id']."'";
			$results = $wpdb->query($insert);
		}
	}
}

function shortcut_media_edit($args) {
	global $wpdb, $shortcut, $attachment;
	
	$file = $_SERVER["SCRIPT_NAME"];
	$break = Explode('/', $file);
	$pfile = $break[count($break) - 1]; 
	
	if($pfile != 'media-upload.php') {
		if($shortcut['trim'] == 'trim') {
			$siteurl = str_replace("www.", "", get_bloginfo('siteurl'))."/".$shortcut['prefix'];
		} else {
			$siteurl = get_bloginfo('siteurl')."/".$shortcut['prefix'];
		}
		
		$attachment_id = $_GET['attachment_id'];
		
		$table_name = $wpdb->prefix . "shortcut";
		$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE  `postid` = '".$attachment_id."'");
		
		if($check == true) {
			$hash = $check[0]->hash;
		} else {
			$hash = shortcut_create_hash();
		}
		
		$html  = "<input type=\"hidden\" name=\"shortcut-unique-org\" value=\"".$hash."\"  class=\"regular-text\"/>";
		$html .= $siteurl."<br />";
		$html .= "<input type=\"text\" name=\"shortcut-unique\" value=\"".$hash."\" class=\"regular-text\"/>";
		
		$field = array('media_shortcut' => array (
			'label' => __('Shortcut'),
			'input' => 'html',
			'html' => $html,
			'helps' => __('')));
	
		return array_merge($args, $field);
	} else {
		$empty = array();
		return array_merge($args, $empty);;
	}
}

function shortcut_admin_main() {
	global $wpdb, $shortcut;
?>
<style>
td.break {word-break: break-word;}
td.sctbl {border-bottom-color: #e3e3e3; border-bottom-style: solid; border-bottom-width: 1px;}
tr:hover td.sctbl {background-color: #f1f1f1; }
</style>
<div class="wrap">
	<h2>Shortcuts</h2>
	<?php settings_fields('shortcut_settings'); ?>
    <h3 id="add">5 Most Recent</h3>
    <table class="form-table widefat">
    	<thead>
			<tr>
				<th></th>
				<th>External URL</th>
				<th>Unique ID</th>
				<th>View Count</th>
				<th>Type</th>
			</tr>
		</thead>
    	<tfoot>
			<tr>
				<th></th>
				<th>External URL</th>
				<th>Unique ID</th>
				<th>View Count</th>
				<th>Type</th>
			</tr>
		</tfoot>
<?php

		$table_name = $wpdb->prefix . "shortcut";
		$list = $wpdb->get_results("SELECT * FROM `".$table_name."` ORDER BY ABS(id) DESC LIMIT 0,5");
		foreach($list as $item) {
			echo "<tbody>";
			echo "<tr>";
			if($item->title) {
				$title = $item->title;
			} else {
				$title = $item->url;
			}
			
			if($item->type == 'wp') {
				$url	= get_permalink($item->postid);
				$title	= get_the_title($item->postid);
				$type	= "Wordpress";
			} elseif($item->type == 'ext') {
				$url	= $item->url;
				if($item->title) {
					$title = $item->title;
				} else {
					$title = $item->url;
				}
				$type	= "External";
			} elseif($item->type == 'wpm') {
				$type	= "Media";
			}
			
			if($shortcut['trim'] == 'trim') {
				$siteurl = str_replace("www.", "", get_bloginfo('siteurl'))."/".$shortcut['prefix'];
			} else {
				$siteurl = get_bloginfo('siteurl')."/".$shortcut['prefix'];
			}
			
			if($item->type == 'wpm') {
				$image	= get_post($item->postid);
				echo "<td width=\"10%\" class=\"sctbl\"><a href=\"".$image->guid."\"><img src=\"".wp_get_attachment_thumb_url($item->postid)."\" height=\"40%\" /></a></td>";
				echo "<td width=\"30%\" class=\"sctbl\"><a href=\"".$image->guid."\">".get_the_title($item->postid)."</a><br>".stripExtension($image->guid)."</td>";
			} else {
				echo "<td width=\"10%\" class=\"sctbl\"></td>";
				echo "<td width=\"30%\" class=\"break sctbl\"><a href=\"".$url."\">".$title."</a></td>";
			}
			
			if(shortcut_check_masked($item->hash)) {
				if($item->url) {
					$image = $item->url;
				} else {
					$image	= get_post($item->postid);
					$image	= $image->guid;
				}
				$extension	= ".".stripExtension($image);
				$note		= " <span class=\"description\"><i>(Masked)</i></span>";
			} else {
				$extension	= "";
				$note		= "";
			}

			echo "<td width=\"30%\" class=\"sctbl\">".$siteurl."<strong>".$item->hash.$extension."</strong>".$note."</td>";
			echo "<td width=\"10%\" class=\"sctbl\">".$item->vcount."</td>";
			echo "<td width=\"10%\" class=\"sctbl\"><a href=\"?page=shortcut-".strtolower($type)."\">".$type."</a></td>";
			echo "</tr>";
			echo "</tbody>";
		}
?>
    </table>
	<br />
    <h3>Top 5</h3>
    <table class="form-table widefat">
    	<thead>
			<tr>
				<th></th>
				<th>External URL</th>
				<th>Unique ID</th>
				<th>View Count</th>
				<th>Type</th>
			</tr>
		</thead>
    	<tfoot>
			<tr>
				<th></th>
				<th>External URL</th>
				<th>Unique ID</th>
				<th>View Count</th>
				<th>Type</th>
			</tr>
		</tfoot>
<?php

		$table_name = $wpdb->prefix . "shortcut";
		$list = $wpdb->get_results("SELECT * FROM `".$table_name."` ORDER BY ABS(vcount) DESC LIMIT 0,5");
		foreach($list as $item) {
			echo "<tbody>";
			echo "<tr>";
			if($item->title) {
				$title = $item->title;
			} else {
				$title = $item->url;
			}
			
			if($item->type == 'wp') {
				$url	= get_permalink($item->postid);
				$title	= get_the_title($item->postid);
				$type	= "Wordpress";
			} elseif($item->type == 'ext') {
				$url	= $item->url;
				if($item->title) {
					$title = $item->title;
				} else {
					$title = $item->url;
				}
				$type	= "External";
			} elseif($item->type == 'wpm') {
				$type	= "Media";
			}
			
			if($shortcut['trim'] == 'trim') {
				$siteurl = str_replace("www.", "", get_bloginfo('siteurl'))."/".$shortcut['prefix'];
			} else {
				$siteurl = get_bloginfo('siteurl')."/".$shortcut['prefix'];
			}
			
			if($item->type == 'wpm') {
				$image	= get_post($item->postid);
				echo "<td width=\"10%\" class=\"sctbl\"><a href=\"".$image->guid."\"><img src=\"".wp_get_attachment_thumb_url($item->postid)."\" height=\"40%\" /></a></td>";
				echo "<td width=\"30%\" class=\"sctbl\"><a href=\"".$image->guid."\">".get_the_title($item->postid)."</a><br>".stripExtension($image->guid)."</td>";
			} else {
				echo "<td width=\"10%\" class=\"sctbl\"></td>";
				echo "<td width=\"30%\" class=\"break sctbl\"><a href=\"".$url."\">".$title."</a></td>";
			}
			
			
			
			if(shortcut_check_masked($item->hash)) {
				if($item->url) {
					$image = $item->url;
				} else {
					$image	= get_post($item->postid);
					$image	= $image->guid;
				}
				$extension	= ".".stripExtension($image);
				$note		= " <span class=\"description\"><i>(Masked)</i></span>";
			} else {
				$extension	= "";
				$note		= "";
			}

			echo "<td width=\"30%\" class=\"sctbl\">".$siteurl."<strong>".$item->hash.$extension."</strong>".$note."</td>";
			echo "<td width=\"10%\" class=\"sctbl\">".$item->vcount."</td>";
			echo "<td width=\"10%\" class=\"sctbl\"><a href=\"?page=shortcut-".strtolower($type)."\">".$type."</a></td>";
			echo "</tr>";
			echo "</tbody>";
		}
?>
    </table>
    <h3 id="add">Stats</h3>
    <table class="form-table">
    	<tr valign="top">
        	<th scope="row">Overall View Average: </th>
			<td><?php echo round(shortcut_get_total('all','average')); ?></td>
    	</tr>
    	<tr valign="top">
        	<th scope="row">Overall Total Views: </th>
			<td><?php echo shortcut_get_total('all','total'); ?></td>
    	</tr>
    	<tr valign="top">
        	<th scope="row">Number of Shortcuts: </th>
			<td>
				All: <?php echo shortcut_get_total('all','rows'); ?><br />
				Wordpress: <?php echo shortcut_get_total('wp','rows'); ?><br />
				Media: <?php echo shortcut_get_total('wpm','rows'); ?><br />
				External: <?php echo shortcut_get_total('ext','rows'); ?>
			</td>
    	</tr>
    </table>
	<table class="form-table">
    	<tr valign="top">
    		<td>Shortcut is  a plugin by <a href="http://www.goreki.com">Paul Taylor</a>. Find more information about it at <a href="http://www.goreki.com/plugins/shortcut/">goreki.com</a></td>
		</tr>
	</table>
</div>
<?php
}

function shortcut_admin_wordpress() {
	global $wpdb, $shortcut;

	if($_GET['page'] == 'shortcut-wordpress') {
		if($_GET['a'] == 'del') {
			if($_GET['id']) {
				$table_name = $wpdb->prefix . "shortcut";
				$insert = "DELETE FROM `".$table_name."` WHERE `id` = ".$_GET['id']."";

				$results = $wpdb->query($insert);
				echo "<div id=\"message\" class=\"updated fade\"><p><strong>Shortcut Deleted.</strong></p></div>";
			}
		}
	}
?>
<style>
td.break {word-break: break-word;}
td.sctbl {border-bottom-color: #e3e3e3; border-bottom-style: solid; border-bottom-width: 1px;}
tr:hover td.sctbl {background-color: #f1f1f1; }
</style>
<div class="wrap">
	<h2>Wordpress Shortcuts</h2>
	<?php settings_fields('shortcut_settings'); ?>
    <table class="form-table widefat">
    	<thead>
			<tr>
				<th>External URL</th>
				<th>Unique ID</th>
				<th>View Count</th>
				<th></th>
			</tr>
		</thead>
<?php
		if(!$_GET['count']) {
			$count	= '0';
			$max	= '10';
		} else {
			$count	= $_GET['count'];
			$max	= $count+10;
		}
?>
		<tfoot>
    	<tr>
    		<th><?php shortcut_get_total('wp','count'); ?></th>
    		<th></th>
<?php
    		if($count <= '0') {
    			echo "<th></th>";
    		} else {
    			$prev = $count-11;
    			echo "<th align=\"right\"><a href=\"?page=shortcut-wordpress&count=".$prev."\">&larr; Newer</a></th>";
    		}
    		if($max >= shortcut_get_total('wp','count')) {
    			echo "<th></th>";
    		} else {
    			$next = $max+1;
    			echo "<th><a href=\"?page=shortcut-wordpress&count=".$next."\">Older &rarr;</a></th>";
    		}
?>
    	</tr>
		</tfoot>
<?php		
		if($shortcut['trim'] == 'trim') {
			$siteurl = str_replace("www.", "", get_bloginfo('siteurl'))."/".$shortcut['prefix'];
		} else {
			$siteurl = get_bloginfo('siteurl')."/".$shortcut['prefix'];
		}

		$table_name = $wpdb->prefix . "shortcut";
		$list = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE `type` = 'wp' ORDER by `id` DESC LIMIT ".$count.",10");
		foreach($list as $item) {
			echo "<tbody>";
			echo "<tr>";
			echo "<td width=\"40%\" class=\"sctbl\"><a href=\"".get_permalink($item->postid)."\">".get_the_title($item->postid)."</a></td>";
			echo "<td width=\"35%\" class=\"sctbl\">".$siteurl."<strong>".$item->hash."</strong></td>";
			echo "<td width=\"15%\" class=\"sctbl\">".$item->vcount."</td>";
			echo "<td width=\"10%\" class=\"sctbl\"><a href=\"?page=shortcut-wordpress&a=del&id=".$item->id."\"><img src=\"".$shortcut['folder']."img/bin.gif\"></a></td>";
			echo "</tr>";
			echo "</tbody>";
		}
?>
		<tbody>
    	<tr>
    		<td></td>
    		<td align="right"><strong>Wordpress Average:</strong></td>
    		<td><?php echo round(shortcut_get_total('wp','average')); ?></td>
    		<td></td>
    	</tr>
		</tbody>
		<tbody>
    	<tr valign="top">
    		<td></td>
    		<td align="right"><strong>Wordpress Total:</strong></td>
    		<td><?php echo shortcut_get_total('wp','total'); ?></td>
    		<td></td>
    	</tr>
		</tbody>
	</table>
		
	<table class="form-table">
    	<tr valign="top">
    		<td>Shortcut is  a plugin by <a href="http://www.goreki.com">Paul Taylor</a>. Find more information about it at <a href="http://www.goreki.com/plugins/shortcut/">goreki.com</a></td>
		</tr>
	</table>
</div>
<?php
}

function shortcut_admin_media() {
	global $wpdb, $shortcut;

	if($_GET['page'] == 'shortcut-media') {
		if($_GET['a'] == 'del') {
			if($_GET['id']) {
				$table_name = $wpdb->prefix . "shortcut";
				$insert = "DELETE FROM `".$table_name."` WHERE `id` = ".$_GET['id']."";

				$results = $wpdb->query($insert);
				echo "<div id=\"message\" class=\"updated fade\"><p><strong>Shortcut Deleted.</strong></p></div>";
			}
		}
	}
?>
<style>
td.break {word-break: break-word;}
td.sctbl {border-bottom-color: #e3e3e3; border-bottom-style: solid; border-bottom-width: 1px;}
tr:hover td.sctbl {background-color: #f1f1f1; }
</style>
<div class="wrap">
	<h2>Media Shortcuts</h2>
	<?php settings_fields('shortcut_settings'); ?>
    <table class="form-table widefat">
    	<thead>
			<tr>
				<th></th>
				<th>External URL</th>
				<th>Unique ID</th>
				<th>View Count</th>
				<th></th>
			</tr>
		</thead>
<?php
		if(!$_GET['count']) {
			$count	= '0';
			$max	= '10';
		} else {
			$count	= $_GET['count'];
			$max	= $count+10;
		}
?>
		<tfoot>
    	<tr>
    		<th><?php shortcut_get_total('wpm','count'); ?></th>
    		<th></th>
    		<th></th>
<?php
    		if($count <= '0') {
    			echo "<th></th>";
    		} else {
    			$prev = $count-11;
    			echo "<th align=\"right\"><a href=\"?page=shortcut-media&count=".$prev."\">&larr; Newer</a></th>";
    		}
    		if($max >= shortcut_get_total('wpm','count')) {
    			echo "<th></th>";
    		} else {
    			$next = $max+1;
    			echo "<th><a href=\"?page=shortcut-media&count=".$next."\">Older &rarr;</a></th>";
    		}
?>
    	</tr>
		</tfoot>
<?php		
		if($shortcut['trim'] == 'trim') {
			$siteurl = str_replace("www.", "", get_bloginfo('siteurl'))."/".$shortcut['prefix'];
		} else {
			$siteurl = get_bloginfo('siteurl')."/".$shortcut['prefix'];
		}

		$table_shortcut = $wpdb->prefix . "shortcut";
		$list = $wpdb->get_results("SELECT * FROM `".$table_shortcut."` WHERE `type` = 'wpm' ORDER by `id` DESC LIMIT ".$count.",10");
		foreach($list as $item) {
			echo "<tbody>";
			echo "<tr>";
			
			
			if(shortcut_check_masked($item->hash)) {
				if($item->url) {
					$image = $item->url;
				} else {
					$image	= get_post($item->postid);
					$image	= $image->guid;
				}
				$extension	= ".".stripExtension($image);
				$note		= " <span class=\"description\"><i>(Masked)</i></span>";
			} else {
				$extension	= "";
				$note		= "";
			}
			
			$image		= get_post($item->postid);
			echo "<td width=\"10%\" class=\"sctbl\"><a href=\"".$image->guid."\"><img src=\"".wp_get_attachment_thumb_url($item->postid)."\" height=\"40%\" /></a></td>";
			echo "<td width=\"30%\" class=\"sctbl\"><a href=\"".$image->guid."\">".get_the_title($item->postid)."</a><br>".stripExtension($image->guid)."</td>";
			echo "<td width=\"35%\" class=\"sctbl\">".$siteurl."<strong>".$item->hash.$extension."</strong><em>".$note."</td>";
			echo "<td width=\"15%\" class=\"sctbl\">".$item->vcount."</td>";
			echo "<td width=\"10%\" class=\"sctbl\"><a href=\"?page=shortcut-media&a=del&id=".$item->id."\"><img src=\"".$shortcut['folder']."img/bin.gif\"></a></td>";
			echo "</tr>";
			echo "</tbody>";
		}
?>
		<tbody>
    	<tr>
    		<td></td>
    		<td></td>
    		<td align="right"><strong>Media Average:</strong></td>
    		<td><?php echo round(shortcut_get_total('wpm','average')); ?></td>
    		<td></td>
    	</tr>
		</tbody>
		<tbody>
    	<tr valign="top">
    		<td></td>
    		<td></td>
    		<td align="right"><strong>Media Total:</strong></td>
    		<td><?php echo shortcut_get_total('wpm','total'); ?></td>
    		<td></td>
    	</tr>
		</tbody>
	</table>
		
	<table class="form-table">
    	<tr valign="top">
    		<td>Shortcut is  a plugin by <a href="http://www.goreki.com">Paul Taylor</a>. Find more information about it at <a href="http://www.goreki.com/plugins/shortcut/">goreki.com</a></td>
		</tr>
	</table>
</div>
<?php
}

function shortcut_admin_external() {
?>

<style>
td.break {word-break: break-word;}
td.sctbl {border-bottom-color: #e3e3e3; border-bottom-style: solid; border-bottom-width: 1px;}
tr:hover td.sctbl {background-color: #f1f1f1; }
</style>
<div class="wrap">
	<h2>External Shortcuts</h2>
	
<?php
	global $wpdb, $shortcut;

	if($shortcut['trim'] == 'trim') {
		$siteurl = str_replace("www.", "", get_bloginfo('siteurl'))."/".$shortcut['prefix'];
	} else {
		$siteurl = get_bloginfo('siteurl')."/".$shortcut['prefix'];
	}
			
	if($_GET['page'] == 'shortcut-external') {
		if($_GET['a'] == 'del') {
			if($_GET['id']) {
				$table_name = $wpdb->prefix . "shortcut";
				$insert = "DELETE FROM `".$table_name."` WHERE `id` = ".$_GET['id']."";

				$results = $wpdb->query($insert);
				echo "<div id=\"message\" class=\"updated fade\"><p><strong>Shortcut Deleted.</strong></p></div>";
			}
		} elseif($_GET['a'] == 'editf') {
			if($_GET['id']) {
				$table_name = $wpdb->prefix . "shortcut";
				$result = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE `id` = ".$_GET['id']."");
?>
				<h3>Edit External Shortcut</h3>
				<form name="shortcut_form_edit" method="post" action="?page=shortcut-external">
					<?php settings_fields('shortcut_edit_ext'); ?>
					<input type="hidden" name="shortcut-id" value="<?php echo $result[0]->id; ?>"  class="regular-text"/>
    				<table class="form-table">
						<tr valign="top">
							<th scope="row">External URL</th>
							<td>
								<input type="hidden" name="shortcut-external-org" value="<?php echo $result[0]->url; ?>"  class="regular-text"/>
								<input type="text" name="shortcut-external" value="<?php echo $result[0]->url; ?>" class="regular-text"/>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Title</th>
							<td>
								<input type="hidden" name="shortcut-title-org" value="<?php echo $result[0]->title; ?>"  class="regular-text"/>
								<input type="text" name="shortcut-title" value="<?php echo $result[0]->title; ?>" class="regular-text"/>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Unique URL</th>
							<td>
								<input type="hidden" name="shortcut-unique-org" value="<?php echo $result[0]->hash; ?>"  class="regular-text"/>
								<?php echo $siteurl; ?>
								<input type="text" name="shortcut-unique" value="<?php echo $result[0]->hash; ?>" class="regular-text"/>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Force Mask</th>
							<td>
							<?php if($result[0]->masked) $check = "checked"; ?>
								<input type="checkbox" name="shortcut-masked" value="true" <?php echo $check; ?>>
			    	    		<span class="description">Mask the shortcut (Note: Only use with an extension! Otherwise one won't be used)</span>
							</td>
						</tr>
    				</table>
					<p class="submit">
						<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
					</p>
				</form>
<?php
			}
		}
	}
	if($_POST['action'] == "update") {
		if($_POST['option_page'] == "shortcut_add_ext") {
			if($_POST['shortcut-external']) {
				if(!shortcut_check_url($_POST['shortcut-external'])) {
					if(!shortcut_check_hash($_POST['shortcut-unique'])) {
						if($_POST['shortcut-masked']) {
							if(stripExtension($_POST['shortcut-external']) == 'png') {
								$masked = $_POST['shortcut-masked'];
							} elseif(stripExtension($_POST['shortcut-external']) == 'gif') {
								$masked = $_POST['shortcut-masked'];
							} elseif(stripExtension($_POST['shortcut-external']) == 'jpg' || stripExtension($_POST['shortcut-external']) == 'jpeg') {
								$masked = $_POST['shortcut-masked'];
							} elseif(stripExtension($_POST['shortcut-external']) == 'css') {
								$masked = $_POST['shortcut-masked'];
							} elseif(stripExtension($_POST['shortcut-external']) == 'js') {
								$masked = $_POST['shortcut-masked'];
							} elseif(stripExtension($_POST['shortcut-external']) == 'pdf') {
								$masked = $_POST['shortcut-masked'];
							} elseif(stripExtension($_POST['shortcut-external']) == 'txt') {
								$masked = $_POST['shortcut-masked'];
							} elseif(stripExtension($_POST['shortcut-external']) == 'zip') {
								$masked = $_POST['shortcut-masked'];
							} elseif(stripExtension($_POST['shortcut-external']) == 'mp3') {
								$masked = $_POST['shortcut-masked'];
							} else {
								$masked = '';
							}
						} else {
							$masked = '';
						}
						
						$title	= shortcut_get_title($_POST['shortcut-external']);
						$date	= date('Y-m-d H:i:s');
						
						$table_name = $wpdb->prefix . "shortcut";
						$insert = "INSERT INTO " . $table_name .
	        	    		" (postid, hash, masked, vcount, type, url, title, date) " .
	        	    		"VALUES ('0','".$_POST['shortcut-unique']."','".$masked."','0','ext','".$_POST['shortcut-external']."', '".$title."','".$date."')";
						
						$results = $wpdb->query($insert);
						
						echo "<div id=\"message\" class=\"updated fade\"><p>External Shortcut Created.</p></div>";
					} else {
						echo "<div id=\"message\" class=\"error fade\"><p>The Shortcut Unique ID <strong>".$_POST['shortcut-unique']."</strong> already exists. Please Choose Another.</p></div>";
					}
				} else {
					echo "<div id=\"message\" class=\"error fade\"><p>URL Already Exists.</p></div>";
				}
			} else {
				echo "<div id=\"message\" class=\"error fade\"><p>Please Enter A URL.</p></div>";
			}
		} elseif($_POST['option_page'] == "shortcut_edit_ext") {
			if($_POST['shortcut-masked']) {
    			if(stripExtension($_POST['shortcut-external']) == 'png') {
    				$masked = $_POST['shortcut-masked'];
    			} elseif(stripExtension($_POST['shortcut-external']) == 'gif') {
    				$masked = $_POST['shortcut-masked'];
    			} elseif(stripExtension($_POST['shortcut-external']) == 'jpg' || stripExtension($_POST['shortcut-external']) == 'jpeg') {
    				$masked = $_POST['shortcut-masked'];
    			} elseif(stripExtension($_POST['shortcut-external']) == 'css') {
    				$masked = $_POST['shortcut-masked'];
    			} elseif(stripExtension($_POST['shortcut-external']) == 'js') {
    				$masked = $_POST['shortcut-masked'];
    			} elseif(stripExtension($_POST['shortcut-external']) == 'pdf') {
    				$masked = $_POST['shortcut-masked'];
    			} elseif(stripExtension($_POST['shortcut-external']) == 'txt') {
    				$masked = $_POST['shortcut-masked'];
    			} elseif(stripExtension($_POST['shortcut-external']) == 'zip') {
					$masked = $_POST['shortcut-masked'];
				} elseif(stripExtension($_POST['shortcut-external']) == 'mp3') {
					$masked = $_POST['shortcut-masked'];
				} else {
    				$masked = '';
    			}
    		} else {
    			$masked = '';
    		}
    		if(!$_POST['shortcut-external']) {
    			$url = $_POST['shortcut-external-org'];
    		} else {
    			$url = $_POST['shortcut-external'];
    		}
    		if(!$_POST['shortcut-title']) {
    			$title = $_POST['shortcut-title-org'];
    		} else {
    			$title = $_POST['shortcut-title'];
    		}
    		if(!$_POST['shortcut-unique']) {
    			$unique = $_POST['shortcut-unique-org'];
    		} else {
    			$unique = $_POST['shortcut-unique'];
    		}
    		
			$table_name = $wpdb->prefix . "shortcut";
			$check = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE `id` = '".$_POST['shortcut-id']."'");
			if($check == true) {
				$insert = "UPDATE `".$table_name."` SET url = '".$url."', title = '".$title."', hash = '".$unique."', masked = '".$masked."' WHERE `id` = '".$_POST['shortcut-id']."'";
				$results = $wpdb->query($insert);
				echo "<div id=\"message\" class=\"updated fade\"><p>Shortcut Edited.</p></div>";
			} else {
				echo "<div id=\"message\" class=\"error fade\"><p>Shortcut cannot be found and edited.</p></div>";
			}
		}
	}
?>
	<?php settings_fields('shortcut_settings'); ?>
    <table class="form-table widefat">
		<thead>
    	<tr valign="top">
    		<th>External URL</th>
    		<th>Unique ID</th>
    		<th>View Count</th>
    		<th></th>
    	</tr>
		</thead>
<?php
		if(!$_GET['count']) {
			$count	= '0';
			$max	= '10';
		} else {
			$count	= $_GET['count'];
			$max	= $count+10;
		}
?>
		<tfoot>
    	<tr>
    		<th><?php shortcut_get_total('ext','count'); ?></th>
    		<th></th>
<?php
    		if($count <= '0') {
    			echo "<th></th>";
    		} else {
    			$prev = $count-11;
    			echo "<th align=\"right\"><a href=\"?page=shortcut-external&count=".$prev."\">&larr; Newer</a></th>";
    		}
    		if($max >= shortcut_get_total('ext','count')) {
    			echo "<th></th>";
    		} else {
    			$next = $max+1;
    			echo "<th><a href=\"?page=shortcut-external&count=".$next."\">Older &rarr;</a></th>";
    		}
?>
    	</tr>
		</tfoot>
<?php
		$table_name = $wpdb->prefix . "shortcut";
		$list = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE `type` = 'ext' ORDER by ABS(id) DESC LIMIT ".$count.",10");
		foreach($list as $item) {
			echo "<tbody>";
			echo "<tr>";
			if($item->title) {
				$title = $item->title;
			} else {
				$title = $item->url;
			}
			echo "<td width=\"40%\" class=\"break sctbl\"><a href=\"".$item->url."\">".$title."</a></td>";
			if(shortcut_check_masked($item->hash)) {
				$extension	= ".".stripExtension($item->url);
				$note		= " <span class=\"description\"><i>(Masked)</i></span>";
			} else {
				$extension	= "";
				$note		= "";
			}
			echo "<td width=\"35%\" class=\"sctbl\">".$siteurl."<strong>".$item->hash.$extension."</strong>".$note."</td>";
			echo "<td width=\"15%\" class=\"sctbl\">".$item->vcount."</td>";
			echo "<td width=\"10%\" class=\"sctbl\"><a href=\"?page=shortcut-external&a=editf&id=".$item->id."\"><img src=\"".$shortcut['folder']."img/edit.gif\"></a> <a href=\"?page=shortcut-external&a=del&id=".$item->id."\"><img src=\"".$shortcut['folder']."img/bin.gif\"></a></td>";
			echo "</tr>";
			echo "</tbody>";
		}
?>
		<tbody>
    	<tr>
    		<td></td>
    		<td align="right"><strong>External Average:</strong></td>
    		<td><?php echo round(shortcut_get_total('ext','average')); ?></td>
    		<td></td>
    	</tr>
		</tbody>
		
		<tbody>
    	<tr>
    		<td></td>
    		<td align="right"><strong>External Total:</strong></td>
    		<td><?php echo shortcut_get_total('ext','total'); ?></td>
    		<td></td>
    	</tr>
		</tbody>
    </table>
    <h3 id="add">Add External Shortcut</h3>
	<form name="shortcut_form_add" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
		<?php settings_fields('shortcut_add_ext'); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">External URL</th>
				<td>
					<input type="text" name="shortcut-external" value="" class="regular-text"/>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Unique URL</th>
				<td>
					<?php $hash = shortcut_create_hash(); ?>
					<input type="hidden" name="shortcut-unique-org" value="<?php echo $hash; ?>"  class="regular-text"/>
<?php echo $siteurl; ?>
					<input type="text" name="shortcut-unique" value="<?php echo $hash; ?>" class="regular-text"/>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Force Mask</th>
				<td>
					<input type="checkbox" name="shortcut-masked" value="true">
	        		<span class="description">Mask the shortcut (Note: Only use with an extension! Otherwise one won't be used)</span>
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Add') ?>" />
		</p>
	</form>
	<table class="form-table">
    	<tr valign="top">
    		<td>Shortcut is  a plugin by <a href="http://www.goreki.com">Paul Taylor</a>. Find more information about it at <a href="http://www.goreki.com/plugins/shortcut/">goreki.com</a></td>
		</tr>
	</table>
</div>
<?php
}

function shortcut_admin_settings() {
	global $shortcut;
	
	if($_POST['action'] == "update") {
		if(!$_POST['shortcut-prefix']) {
			$shortcutprefix = '_';
		} else {
			$shortcutprefix = $_POST['shortcut-prefix'];
		}
		$shortcuttrim	= $_POST['shortcut-trim'];
		$extension		= $_POST['shortcut-custom-mask'];
		$length			= $_POST['shortcut-hash-length'];
		if(!is_numeric($length)) {
			$length = '5';
		}
		update_option('shortcut_prefix',$shortcutprefix);
		update_option('shortcut_trim',$shortcuttrim);
		update_option('shortcut_custom_mask',$extension);
		update_option('shortcut_hash_length',$length);
		echo "<div id=\"message\" class=\"updated fade\"><p>Settings Updated.</p></div>";
	}
	
if($shortcut['trim'] == 'trim') {
    $siteurl = str_replace("www.", "", get_bloginfo('siteurl'));
} else {
    $siteurl = get_bloginfo('siteurl');
}
	
?>

<div class="wrap">
	<h2>Shortcut Settings</h2>
	<form name="shortcut_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
		<?php settings_fields('shortcut_settings'); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">URL Prefix</th>
				<td>
					<input type="text" name="shortcut-prefix" value="<?php echo get_option('shortcut_prefix'); ?>"  class="regular-text shortcut-prefix"/>
	        		<span class="description">Prefix for the short url. (eg. <?php echo $siteurl; ?>/_example) <i>Default: '_'</i></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Trim URL</th>
				<td>
	        		<select name="shortcut-trim">
	        		<?php
			        $current_trim	= get_option('shortcut_trim');
			        if($current_trim == "trim") {
			        	echo "<option value=\"trim\" selected>Trim</option>\n";
			        	echo "<option value=\"no\">Do Not Trim</option>\n";
			        } else {
			        	echo "<option value=\"trim\">Trim</option>\n";
			        	echo "<option value=\"no\" selected>Do Not Trim</option>\n";
			        }
			        ?>
			        </select>
	        		<span class="description">the "www." from get_shortcut() function.</span>
        		</td>
        	</tr>
			<tr valign="top">
				<th scope="row">Custom Mask Extension</th>
				<td>
					<?php
					if(get_option('shortcut_custom_mask')) {
						$extension = get_option('shortcut_custom_mask');
					} else {
						$extension = 'sc';
					}
					?>
					<input type="text" name="shortcut-custom-mask" value="<?php echo $extension; ?>"  class="regular-text"/>
	        		<span class="description"><i>Default: 'sc'</i></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Custom ID Length</th>
				<td>
					<?php
					if(get_option('shortcut_hash_length')) {
						$length = get_option('shortcut_hash_length');
					} else {
						$length = '5';
					}
					?>
					<input type="text" name="shortcut-hash-length" value="<?php echo $length; ?>"  class="regular-text"/>
	        		<span class="description"></span>
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>
	<table class="form-table">
    	<tr valign="top">
    		<td>Shortcut is  a plugin by <a href="http://www.goreki.com">Paul Taylor</a>. Find more information about it at <a href="http://www.goreki.com/plugins/shortcut/">goreki.com</a></td>
		</tr>
	</table>
</div>
<?php
}

function shortcut_help($text, $screen) {
	global $shortcut;
	// Check we're only on my Settings page
	if (strcmp($screen, 'shortcut_page_shortcut-settings') == 0 ) {
 
		$text  = '<h5>Help</h5>';
		$text .= '<strong>URL Prefix</strong> This allows you to set the prefix of your Shortcuts. The default value is an underscore ("_"). For example, entering "goto-" would make your URL\'s look like: http://www.domain.com/goto-01fds.<br /><br />';
		$text .= '<strong>Trim URL</strong> Trimming the URL removes the www. from your Shortcuts, making it less characters. This is very useful for usage in situations where you need as few characters as possible. Twitter is a good example of this.<br /><br />';
		$text .= '<strong>Custom Mask Extension</strong> This allows you to use a custom extension for certain masked files. For example using .sc rather than .jpg with an image.<br /><br />';
		$text .= '<strong>Custom ID Length</strong> When creating a new post/page, adding new media or an external link, you are automatically given an unique string of 5 characters. Changing this number allows you to make that unique string shorter or longer. <em>Note: This does <u>not</u> change existing Shortcuts.</em><br /><br />';
		return $text;
	} elseif (strcmp($screen, 'shortcut_page_shortcut-external') == 0 ) {
 
		$text  = '<h5>Help</h5>';
		$text .= 'This is a table of all your External URL\'s. You can delete a Shortcut from here by clicking on <img src="'.$shortcut['folder'].'img/bin.gif"> or you can edit a Shortcut by clicking on <img src="'.$shortcut['folder'].'img/edit.gif"> <br /><br />';
		
		$text .= '<h5>Help - Adding An External Shortcut</h5>';
		$text .= 'External Shortcuts work in exactly the same way as a Post or Page Shortcut. Except it allows you to create Shortcuts to external websites. You are given or can create a Unique URL that redirects to your desired URL.<br /><br />';
		$text .= '<strong>External URL</strong> This is the Target Website Address (URL) which you would like to redirect to.<br /><br />';
		$text .= '<strong>Unique URL</strong> This is the Unique Address that you will be redirecting from, this can be left as it is or changed to a Custom Unique URL.<br /><br />';
		$text .= '<strong>Force Mask</strong> If you are linking to an image you can hide the Target URL by checking this. You will be given a Shortcut as usual but with a file extension. This will prevent redirection, hiding the Target URL.<br /><br />';
		return $text;
	}
	// Let the default WP Dashboard help stuff through on other Admin pages
	return $text;
}

function shortcut_on_activation() {
   global $wpdb;
   global $lu_db_version;

	//** Create Table **//
   $table_name = $wpdb->prefix . "shortcut";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      $sql = "CREATE TABLE " . $table_name . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  postid text NOT NULL,
	  hash text NOT NULL,
	  masked text NOT NULL,
	  vcount text NOT NULL,
	  type text NOT NULL,
	  url text NOT NULL,
	  title text NOT NULL,
	  date text NOT NULL,
	  UNIQUE KEY id (id)
	);";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
 
      add_option("lu_db_version", $lu_db_version);
   }

	//** Create Setting Defaults **//
	add_option('shortcut_prefix','_','','');
	add_option('shortcut_trim','no','','');
	add_option('shortcut_custom_mask','sc','','');
	add_option('shortcut_hash_length','5','','');
}

function shortcut_settings_page() {
	add_menu_page('Shortcut', 'Shortcut', 'manage_options', 'shortcut-main', 'shortcut_admin_main');
	add_submenu_page( 'shortcut-main', 'Wordpress Shortcuts', 'Wordpress Shortcuts', 'manage_options', 'shortcut-wordpress', 'shortcut_admin_wordpress');
	add_submenu_page( 'shortcut-main', 'Media Shortcuts', 'Media Shortcuts', 'manage_options', 'shortcut-media', 'shortcut_admin_media');
	add_submenu_page( 'shortcut-main', 'External Shortcuts', 'External Shortcuts', 'manage_options', 'shortcut-external', 'shortcut_admin_external');
	add_submenu_page( 'shortcut-main', 'Add External Shortcut', 'Add External Shortcut', 'manage_options', 'shortcut-external#add', 'shortcut_admin_external');
	add_submenu_page( 'shortcut-main', 'Settings', 'Settings', 'manage_options', 'shortcut-settings', 'shortcut_admin_settings');
	// add_options_page("shortcut-main", "shortcut Settings", 1, "shortcut-settings", "shortcut_admin");  
}

register_activation_hook(__FILE__, 'shortcut_on_activation');
add_action('admin_menu', 'shortcut_settings_page');
add_action('admin_menu', 'shortcut_custom_boxes');
add_action('contextual_help', 'shortcut_help', 10, 2);

add_filter('get_header', 'shortcut_goto');
add_filter('404_template', 'shortcut_errors');
add_action('publish_post', 'shortcut_publish_post');
add_action('publish_page', 'shortcut_publish_post');

// MEDIA ATTACHMENT FILTERS
add_filter('attachment_fields_to_save', 'shortcut_media_save', 5);
add_filter('attachment_fields_to_edit', 'shortcut_media_edit', 5);

?>
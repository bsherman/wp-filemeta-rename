<?php
/*
Plugin Name: Attached File Meta Rename
Plugin URI: http://holyarmy.org/benjamin/projects/filemeta-rename
Description: Provides a way to fix meta file pointers in the database after moving the files on the filesystem.
Version: 1.0.0
Author: Benjamin Sherman
Author URI: http://holyarmy.org/benjamin/
*/


if (!class_exists('AttachedFileMetaRename')) {
  class AttachedFileMetaRename {
    function AttachedFileMetaRename() {
    }  
  
    function printAdminPage() {
      $errors = array(); // add errors here ( 'fieldname'=>'message' )
      $path_old = ""; // when provided, this will match old paths
      $path_new = ""; // when provided, this will replace old paths
      $execute_replace = false;
      $preview_replace = false;

      if (isset($_POST['replace_preview'])) {
	// since preview was pushed check the input 
	if (!empty($_POST['path_old_input'])) {
	  $path_old = $_POST['path_old_input'];
	} else {
	  $errors['path_old_input'] = "Old Path is required.";
	}
	if (!empty($_POST['path_new_input'])) {
	  $path_new = $_POST['path_new_input'];
	} else {
	  $errors['path_new_input'] = "New Path is required.";
	}
	if (0 == count($errors)) {
	  $preview_replace = true;
	}
      } // end if POST file_meta_preview

      if (isset($_POST['replace_execute'])) {
	// since execute was pushed verify the input 
	if (!empty($_POST['path_old_input'])) {
	  if (!empty($_POST['path_old_hidden'])) {
	    $path_old = $_POST['path_old_input'];
	    if ($_POST['path_old_input']!=$_POST['path_old_hidden']) {
	      $errors['path_old_input'] = "Old Path has changed. You should rerun preview.";
	    }
	  }
	} else {
	  $errors['path_old_input'] = "Old Path is required, even after preview.";
	}
	if (!empty($_POST['path_new_input'])) {
	  if (!empty($_POST['path_new_hidden'])) {
	    $path_new = $_POST['path_new_input'];
	    if ($_POST['path_new_input']!=$_POST['path_new_hidden']) {
	      $errors['path_new_input'] = "New Path has changed. You should rerun preview.";
	    }
	  }
	} else {
	  $errors['path_new_input'] = "New Path is required, even after preview.";
	}
	if (0 == count($errors)) {
	  $execute_replace = true;
	}
      } // end if POST file_meta_execute
?>
<style type="text/css">
.input {
  border-style: dashed;
  border-width: 1px;
  padding-left: 8px;
}
.errors {
  color: #CC0000;
  font-weight: bold;
}
.errors li {
  list-style-type: none;
}
.preview_list li {
  list-style-type: none;
  margin: 0;
  padding: 8px;
}
.odd_li {
  background-color: #ffffff;
}
.even_li {
  background-color: #e2e2e2;
}
.old_file {
  margin-left: 10px;
  background-color: #ff6666;
}
.new_file {
  margin-left: 10px;
  background-color: #99ffcc;
}
</style>
<div class="wrap">
<!-- <?php print_r($_POST); ?> -->
<h2>Attached File Meta Rename</h2>
<p>This plugin will execute a database update on your wordpress attached file meta information.
<ul>
<li>It finds all matches of <em>Old Path</em> and replaces with <em>New Path</em>.</li>
<li>After providing input, a <em>Preview</em> will provide feedback on which files will be modified and and how.
<li>After <em>Preview</em>, the actual update can be <em>Excecute</em>d.</li>
</ul>
</p>
<p>Warning: You should ALWAYS backup your database before using this tool. It will make the changes YOU request, thus, it could mess things up if they are working, or confuse them more if they are already broken.</p>
<!-- input form -->
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
  <div class="input">
    <h3>Input</h3>
<?php if (0 != count($errors)) { ?>
    <div class="errors">
      <ul>
  <?php foreach ($errors as $field=>$error) { ?>
      <li><?php echo $error; ?></li>
  <?php } ?>
      </ul>
    </div> <!-- end div class="errors" -->
<?php } ?>
    <p>
      <label for "path_old_input">Old Path: </label>
      <input type="text" id="path_old_input" name="path_old_input" size="50"<?php if(!empty($path_old)){echo ' value="'.$path_old.'"';} ?>>
      <?php if(!empty($path_old)) { ?>
      <input type="hidden" id="path_old_hidden" name="path_old_hidden" value="<?php echo $path_old; ?>">
      <?php } ?>
    </p>
    <p>
      <label for "path_new_input">New Path: </label>
      <input type="text" id="path_new_input" name="path_new_input" size="50"<?php if(!empty($path_new)){echo ' value="'.$path_new.'"';} ?>>
      <?php if(!empty($path_new)) { ?>
      <input type="hidden" id="path_new_hidden" name="path_new_hidden" value="<?php echo $path_new; ?>">
      <?php } ?>
    </p>
    <p> <button type="submit" name="replace_preview" value="true">Preview</button> </p>
  </div> <!-- end div class="input" -->

<?php if ($preview_replace || $execute_replace) { // start if preview/replace show action panel
  $args = array(
    'post_type' => 'attachment',
    'numberposts' => -1,
    'post_status' => null,
    'post_parent' => null, // any parent
    );
?>
  <div class="preview_list">
<?php if ($preview_replace) { ?>
    <h3>Rename Preview</h3>
<?php } elseif ($execute_replace) { ?>
    <h3>Rename Execution</h3>
<?php
      } // end if preview/execute for title

  $attachments = get_posts($args);
  if ($attachments && (0 < count($attachments))) { // start if attachments
    $matches = array();
    foreach ($attachments as $post) {
      setup_postdata($post);
      $_wp_attached_file = get_post_meta( $post->ID, '_wp_attached_file', true );
      $_wp_attachment_metadata = get_post_meta( $post->ID, '_wp_attachment_metadata', true );

      if ( (FALSE !== strpos($_wp_attached_file, $path_old)) || 
	   (FALSE !== strpos($_wp_attachment_metadata['file'], $path_old)) ) {
	// matched a file so store
	$matches[] = $post;
      }
    }
    
    if ($matches && (0 < count($matches))) { // start if matches
?>
      <ul>
<?php
      $counter=0;
      foreach ($matches as $post) {
	$counter++;
	$evenodd = "odd_li";
	if ($counter%2 == 0) { $evenodd = "even_li"; }
	
	setup_postdata($post);
	$_wp_attached_file = get_post_meta( $post->ID, '_wp_attached_file', true );
	$_wp_attachment_metadata = get_post_meta( $post->ID, '_wp_attachment_metadata', true );

	echo "<li class=\"".$evenodd."\">\n"
	    . "<strong>ID ".$post->ID.": ".$post->post_title."</strong><br/>\n";
	if (FALSE !== strpos($_wp_attached_file, $path_old)) {
	  $_wp_attached_file_new = str_replace($path_old, $path_new, $_wp_attached_file);
	  echo "_wp_attached_file:";
	  if ($execute_replace) {
	    delete_post_meta($post->ID, '_wp_attached_file');
	    add_post_meta($post->ID, '_wp_attached_file', $_wp_attached_file_new);
	    echo " <strong>RENAMED!</strong>";
	  }
	  echo "<br/>\n";
	  echo "<span class=\"old_file\">".$_wp_attached_file."</span><br/>\n";
	  echo "<span class=\"new_file\">".$_wp_attached_file_new."</span><br/>\n";
	}
	if (FALSE !== strpos($_wp_attachment_metadata['file'], $path_old)) {
	  $_wp_attachment_metadata_old = $_wp_attachment_metadata['file'];
	  $_wp_attachment_metadata_new = str_replace($path_old, $path_new, $_wp_attachment_metadata['file']);
	  echo "_wp_attachment_metadata['file']:";
	  if ($execute_replace) {
	    $_wp_attachment_metadata['file'] = $_wp_attachment_metadata_new;
	    delete_post_meta($post->ID, '_wp_attachment_metadata');
	    add_post_meta($post->ID, '_wp_attachment_metadata', $_wp_attachment_metadata);
	    echo " <strong>RENAMED!</strong>";
	  }
	  echo "<br/>\n";
	  echo "<span class=\"old_file\">".$_wp_attachment_metadata_old."</span><br/>\n";
	  echo "<span class=\"new_file\">".$_wp_attachment_metadata_new."</span><br/>\n";
	}
	echo "</li>\n";
      } // end foreach matches
?>
      </ul>
<?php if ($preview_replace) { 
?>
      <p>If everything looks like it will be renamed properly, you can now execute a rename for these <?php echo count($matches); ?> attachments!<br/><button type="submit" name="replace_execute" value="true">Execute</button> </p>
<?php } 
      if ($execute_replace) {
?>
      <p>Renamed <?php echo count($matches); ?> attachments!</p>
<?php } 
    } else {
?>
  <p>No matching file attachments found for Old Path: "<?php echo $path_old; ?>"</p>
<?php
    } // end if matches
  } else {
?>
  <p>No file attachments exist in this wordpress intance.</p>
<?php } // end if attachments ?>
  </div> <!-- end div class="preview_list" -->
<?php } // end if preview/replace show action panel ?>
</form> <!-- end input form -->
</div> <!-- end div class="wrap" -->

<?php
    } // end function printAdminPage()
  } // end class FileMetaRename
} // end if (!class_exists('AttachedFileMetaRename')) 

if (class_exists("AttachedFileMetaRename")) {
  $attachedFileMetaRename = new AttachedFileMetaRename();
}

if (!function_exists("AttachedFileMetaRename_ap")) {
  function AttachedFileMetaRename_ap() {
    global $attachedFileMetaRename;
    if (!isset($attachedFileMetaRename)) {
      return;
    }
    if ( current_user_can('manage_options') && function_exists('add_options_page') ) {
      add_options_page('Attached File Meta Rename', 'File Meta Rename', 9, basename(__FILE__), array(&$attachedFileMetaRename, 'printAdminPage'));
      add_filter('plugin_action_links', 'filemeta_rename_filter_plugin_actions', 10, 2);
    }
  }
}

if (isset($attachedFileMetaRename)) {
  add_action('admin_menu', 'AttachedFileMetaRename_ap');
}

function filemeta_rename_filter_plugin_actions($links, $file) {
  static $this_plugin;

  if ( !$this_plugin ) $this_plugin = plugin_basename(__FILE__);

  if ( $file == $this_plugin ) {
    $rename_link = '<a href="options-general.php?page=filemeta-rename.php">' . __('Rename') . '</a>';
    $links = array_merge( array($rename_link), $links); // before other links
  }
  return $links;
}
?>

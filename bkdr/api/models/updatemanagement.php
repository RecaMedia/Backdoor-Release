<?php
/*
@category   CMS
@package    Backdoor - Your Online Companion Editor
@author     Shannon Reca | shannonreca.com
@copyright  2018 Shannon Reca
@usage      For more information visit https://github.com/RecaMedia/Backdoor
@license    https://github.com/RecaMedia/Backdoor/blob/master/LICENSE
@version    v2.0.6
@since      01/12/18
*/

class UpdateManagement extends Controller{

	function __construct($db,$domain,$config) {
		// Initialize the DirectoryLister object
		$this->db = $db;
		$this->domain = $domain;
    $this->config = $config;
  }

  private function httpPost($url, $data = false) {
    $curl = curl_init($url);
    if ($data) {
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
      'Bkdr-Timestamp:'.strtotime(date('Y-m-d', time()). '00:00:00')
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
    $response = curl_exec($curl);   
    if (curl_errno($curl)) {
      $response = false; 
    } 
    curl_close($curl);
    return $response;
  }

  private function recurse_copy($src, $dst) {
		$dir = opendir($src);
		$result = ($dir === false ? false : true);
		if ($result !== false) {
      while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' ) && $result) {
          if ( is_dir($src . DIRECTORY_SEPARATOR . $file) ) {
            $result = $this->recurse_copy($src . DIRECTORY_SEPARATOR . $file,$dst . DIRECTORY_SEPARATOR . $file);
          } else { 
            $result = copy($src . DIRECTORY_SEPARATOR . $file,$dst . DIRECTORY_SEPARATOR . $file);
          }
        }
      }
      closedir($dir);
		}
		return $result;
  }
  
  private function mergeConfig($old_config, $new_config) {
    $success = false;

    $o_config = (array)json_decode(file_get_contents($old_config), true);
    $n_config = (array)json_decode(file_get_contents($new_config), true);
    
    foreach($n_config as $key => $n_value) {
      if (array_key_exists($key,$o_config)) {
        $n_config[$key] = $o_config[$key];
      } else {
        $n_config[$key] = $n_value;
      }
    }

    $config_file = fopen($old_config, 'w');
    $json_string = json_encode($n_config, JSON_PRETTY_PRINT);
    if (fwrite($config_file, $json_string) === false) {
      $cleanInstall = false;
    } else {
      $cleanInstall = true;
    }
    fclose($config_file);
    chmod($old_config, 0744);
    return $cleanInstall;
  }

  // public function sampleCall() {
  //   $return = $this->httpPost('http://domain.com/latest/update/');

  //   if ($return == false) {
  //     return array(
  //       'success' => false,
  //       'statusMessage' => "Error getting latest from BKDR."
  //     );
  //   } else {
  //     return json_decode($return, true);
  //   }
  // }
  
  public function processUpdate() {

    if (isset($_POST['downloadUrl']) && $_POST['downloadUrl'] != "") {
      getcwd();
      chdir('../');
      $root = getcwd();
      $update_folder = $root . DIRECTORY_SEPARATOR . "update" . DIRECTORY_SEPARATOR;
      $old_bkdr = $root . DIRECTORY_SEPARATOR;
      $new_bkdr = $update_folder . "latest_bkdr" . DIRECTORY_SEPARATOR . "bkdr" . DIRECTORY_SEPARATOR;
      $stored_bkdr = $update_folder . "previous_bkdr" . DIRECTORY_SEPARATOR;

      $url = $_POST['downloadUrl'];
      // $zipFile = $update_folder . "update.zip";
      // $zipResource = fopen($zipFile, "w");

      // // Get The Zip File From Server.
      // $ch = curl_init();
      // curl_setopt($ch, CURLOPT_URL, $url);
      // curl_setopt($ch, CURLOPT_FAILONERROR, true);
      // curl_setopt($ch, CURLOPT_HEADER, 0);
      // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      // curl_setopt($ch, CURLOPT_AUTOREFERER, true);
      // curl_setopt($ch, CURLOPT_BINARYTRANSFER,true);
      // curl_setopt($ch, CURLOPT_TIMEOUT, 10);
      // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
      // curl_setopt($ch, CURLOPT_FILE, $zipResource);
      // $page = curl_exec($ch);
      // curl_close($ch);

      // // Fail if not downloaded.
      // if(!$page) {
      //   return array(
      //     'success' => false,
      //     'statusMessage' => "Download failed."
      //   );
      // }

      // Extract Zip File.
      $zip = new ZipArchive;
      // Fail if not extracted.
      if($zip->open($url) != "true"){
        return array(
          'success' => false,
          'statusMessage' => "Zip extraction failed."
        );
      } else {
        $zip->extractTo($update_folder . DIRECTORY_SEPARATOR . "latest_bkdr");
        $zip->close();

        $run_db_update      = $old_bkdr . "api" . DIRECTORY_SEPARATOR . "updateDB.php";

        $old_bkdr_db        = $old_bkdr . "api" . DIRECTORY_SEPARATOR . "core.db";
        // Old directories.
        $old_bkdr_api       = $old_bkdr . "api" . DIRECTORY_SEPARATOR;
        $old_bkdr_assets    = $old_bkdr . "assets" . DIRECTORY_SEPARATOR;
        $old_bkdr_dirlister = $old_bkdr . "dir-lister" . DIRECTORY_SEPARATOR;
        $old_bkdr_term      = $old_bkdr . "term" . DIRECTORY_SEPARATOR;
        // Old files.
        $old_bkdr_config    = $old_bkdr . "config.json";
        $old_bkdr_index     = $old_bkdr . "index.php";

        $temp_bkdr_db       = $new_bkdr . "core.db";
        // New directories.
        $new_bkdr_api       = $new_bkdr . "api" . DIRECTORY_SEPARATOR;
        $new_bkdr_assets    = $new_bkdr . "assets" . DIRECTORY_SEPARATOR;
        $new_bkdr_dirlister = $new_bkdr . "dir-lister" . DIRECTORY_SEPARATOR;
        $new_bkdr_term      = $new_bkdr . "term" . DIRECTORY_SEPARATOR;
        // New files.
        $new_bkdr_config    = $new_bkdr . "update" . DIRECTORY_SEPARATOR . "sample_config.json";
        $new_bkdr_index     = $new_bkdr . "index.php";

        // Proceed if DB is properly copied.
        if (copy($old_bkdr_db, $temp_bkdr_db)) {
          $process_msg = array("App successfully downloaded and extracted.");
          $process_msg[] = "DB successfully reserved.";

          // Update API
          if (is_dir($new_bkdr_api) && is_dir($old_bkdr_api)) {
            if ($this->recurse_copy($new_bkdr_api, $old_bkdr_api)) {
              $process_msg[] = "API successfully updated.";
            } else {
              $process_msg[] = "API not updated.";
            }
          } else {
            $process_msg[] = "Error finding API directory.";
          }

          // Update assets
          if (is_dir($new_bkdr_assets) && is_dir($old_bkdr_assets)) {
            if ($this->recurse_copy($new_bkdr_assets, $old_bkdr_assets)) {
              $process_msg[] = "Assets successfully updated.";
            } else {
              $process_msg[] = "Assets not updated.";
            }
          } else {
            $process_msg[] = "Error finding Assets directory.";
          }

          // Update Directory Lister
          if (is_dir($new_bkdr_dirlister) && is_dir($old_bkdr_dirlister)) {
            if ($this->recurse_copy($new_bkdr_dirlister, $old_bkdr_dirlister)) {
              $process_msg[] = "Directory lister successfully updated.";
            } else {
              $process_msg[] = "Directory lister not updated.";
            }
          } else {
            $process_msg[] = "Error finding Directory Lister directory.";
          }

          // Update terminal
          if (is_dir($new_bkdr_term) && is_dir($old_bkdr_term)) {
            if ($this->recurse_copy($new_bkdr_term, $old_bkdr_term)) {
              $process_msg[] = "Terminal successfully updated.";
            } else {
              $process_msg[] = "Terminal not updated.";
            }
          } else {
            $process_msg[] = "Error finding Terminal directory.";
          }

          // Merge configurations
          if ($this->mergeConfig($old_bkdr_config, $new_bkdr_config)) {
            $process_msg[] = "Config successfully updated.";
          } else {
            $process_msg[] = "Config not updated.";
          }
          // Update index
          if (copy($new_bkdr_index, $old_bkdr_index)) {
            $process_msg[] = "Index successfully updated.";
          } else {
            $process_msg[] = "Index not updated.";
          }

          if (copy($temp_bkdr_db, $old_bkdr_db)) {
            $process_msg[] = "DB successfully restored.";
            // Run DB update...
            include $run_db_update;
          } else {
            $process_msg[] = "DB not restored.";
          }

          return array(
            "success" => true,
            'statusMessage' => implode("<br/>", $process_msg)
          );
        } else {
          return array(
            'success' => false,
            'statusMessage' => "Update failed."
          );
        }
      }
    } else {
      return array(
        'success' => false,
        'statusMessage' => "Update failed."
      );
    }
  }
}
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

class UserManagement extends Controller{

	function __construct($db,$domain,$config) {
		$this->db = $db;
		$this->domain = $domain;
		$this->config = $config;
	}

	public function userSignin(){
		$email = (isset($_POST['email']) ? $_POST['email']:"");
		$pass = (isset($_POST['password']) ? $_POST['password']:"");
		$error = false;

		if ($email == "" || $pass == "") {
			$error = true;
		}

		if (!$error) {

			$queryLogin = $this->db->prepare("SELECT * FROM Users WHERE Email=:email AND Password=:pass AND Confirm=1");
			$queryLogin->bindValue(':email',$email);
			$queryLogin->bindValue(':pass',$pass);
			$result = $queryLogin->execute();
			$row = $result->fetchArray(SQLITE3_ASSOC);

		  if ($row) {
				$key = base64_encode($row['Email'].":".$row['Password']);
				$user = array(
					"fname" => $row['Fname'],
					"lname" => $row['Lname'],
					"email" => $row['Email'],
					"admin" => ($row['Admin']?true:false),
					"active" => ($row['Confirm']?true:false),
					"code"  => $row['Code']
				);

				$_SESSION['bdmemberkey'] = $key;
				$_SESSION['bdmembercode'] = $row['Code'];

				return array(
					'success' => true,
					'statusMessage' => "Sign-in successful.",
					'user' => $user,
          'key' => $key
				);
			} else {
				return array(
					'success' => false,
					'statusMessage' => "Sign-in failed."
				);
			}
		} else {
			return array(
				'success' => false,
				'statusMessage' => "Missing values."
			);
		}
	}

	public function adminCheck($userid){
		$userLogin = $this->db->prepare("SELECT * FROM Users WHERE Admin=1");
		$result = $userLogin->execute();

		while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
			if ($row['Code'] == $userid && $userid != "") {
				return true;
				break;
			}
		}

		return false;
	}

	public function updateUser() {
		$code = (isset($_POST['usercode']) ? $_POST['usercode']:"");
		$email = (isset($_POST['email']) ? $_POST['email']:"");
		$fname = (isset($_POST['fname']) ? $_POST['fname']:"");
		$lname = (isset($_POST['lname']) ? $_POST['lname']:"");
		$error = false;

		if ($email == "" || $fname == "" || $lname == "") {
			$error = true;
			$errorMsg = "Missing values.";
		} else if ($_SESSION['bdmembercode'] != $code) {
			$error = true;
			$errorMsg = "There is a mismatch with your account id.";
		}

		if (!$error) {
			$updateUser = $this->db->prepare("UPDATE Users SET Fname=:fname, Lname=:lname, Email=:email WHERE Code=:code");
			$updateUser->bindValue(':email',$email);
			$updateUser->bindValue(':lname',$lname);
			$updateUser->bindValue(':fname',$fname);
			$updateUser->bindValue(':code',$code);
			$result = $updateUser->execute();

			if ($result) {
				return array(
					'success' => true,
					'statusMessage' => "Account successfully updated.",
					'user' => $this->getUser($code)['user']
				);
			} else {
				return array(
					'success' => false,
					'statusMessage' => "There was an error updating account."
				);
			}

		} else {
			return array(
				'success' => false,
				'statusMessage' => $errorMsg
			);
		}
	}

	public function updateUserPass() {
		$code = (isset($_POST['usercode']) ? $_POST['usercode']:"");
		$newPass = (isset($_POST['newpass']) ? $_POST['newpass']:"");
		$error = false;

		if ($newPass == "" || $code == "") {
			$error = true;
			$errorMsg = "Missing values.";
		} else if ($_SESSION['bdmembercode'] != $code) {
			$error = true;
			$errorMsg = "There is a mismatch with your account id.";
		}

		if (!$error) {
			// $newPass = $this->encrypt($newPass);
			$updateUser = $this->db->prepare("UPDATE Users SET Password=:newpass WHERE Code=:code");
			$updateUser->bindValue(':newpass',$newPass);
			$updateUser->bindValue(':code',$code);
			$result = $updateUser->execute();

			if ($result) {
				return array(
					'success' => true,
					'statusMessage' => "Password successfully updated."
				);
			} else {
				return array(
					'success' => false,
					'statusMessage' => "There was an error updating your password."
				);
			}

		} else {
			return array(
				'success' => false,
				'statusMessage' => $errorMsg
			);
		}
	}

	public function activateUser() {
		$adminCode = (isset($_POST['admincode'])?$_POST['admincode']:"");

		if ($this->adminCheck($adminCode)) {
			$code = (isset($_POST['usercode']) ? $_POST['usercode']:"");
			$activate = (isset($_POST['confirm']) ? $_POST['confirm']:0);

			if ($activate==1) {
				$rsptxt = "activated";
			} else {
				$rsptxt = "deactivated";
			}

			$updateUser = $this->db->prepare("UPDATE Users SET Confirm=:activate WHERE Code=:code");
			$updateUser->bindValue(':activate',$activate);
			$updateUser->bindValue(':code',$code);
			$result = $updateUser->execute();

			if ($result) {
				return array(
					'success' => true,
					'statusMessage' => "Account $rsptxt."
				);
			} else {
				return array(
					'success' => false,
					'statusMessage' => "There was an error activating account."
				);
			}
		} else {
			return array(
				'success' => false,
				'statusMessage' => "You must be an admin to update these settings."
			);
		}
	}

	public function makeUserAdmin() {
		$adminCode = (isset($_POST['admincode'])?$_POST['admincode']:"");

		if ($this->adminCheck($adminCode)) {
			$code = (isset($_POST['usercode']) ? $_POST['usercode']:"");
			$makeadmin = (isset($_POST['makeadmin']) ? $_POST['makeadmin']:0);

			if ($makeadmin==1) {
				$rsptxt = "is now admin";
			} else {
				$rsptxt = "is no longer an admin";
			}

			$updateUser = $this->db->prepare("UPDATE Users SET Admin=:makeadmin WHERE Code=:code");
			$updateUser->bindValue(':makeadmin',$makeadmin);
			$updateUser->bindValue(':code',$code);
			$result = $updateUser->execute();

			if ($result) {
				return array(
					'success' => true,
					'statusMessage' => "Account $rsptxt."
				);
			} else {
				return array(
					'success' => false,
					'statusMessage' => "There was an error setting admin for this account."
				);
			}
		} else {
			return array(
				'success' => false,
				'statusMessage' => "You must be an admin to update these settings."
			);
		}
	}

	public function createUser($userCode = "",$email = "",$pass = "",$admin = "") {
		$errors = false;
		$userCode = ($userCode != "" ? $userCode:$_POST['usercode']);

		$settingsQuery = $this->db->prepare("SELECT * FROM Settings WHERE SettingType='bkdr_open_reg'");
    $result = $settingsQuery->execute();
		$row = $result->fetchArray(SQLITE3_ASSOC);
		$isOpen = $row['SettingValue'] == "false" ? false : ($row['SettingValue'] == "true" ? true : $row['SettingValue']);

		if ($this->adminCheck($userCode) || $isOpen || $admin) {

			$fname = (isset($_POST['fname']) ? $_POST['fname']:"User");
			$lname = (isset($_POST['lname']) ? $_POST['lname']:"User");
			$email = ($email != "" ? $email:$_POST['email']);
			$pass = ($pass != "" ? $pass:$_POST['pass']);
			$admin = ($admin != "" ? $admin:0);
			$code = hash('sha256', $email.date("Y-m-d H:m:s"));

			if ($email == "" || $pass == "") {
				$msg = "Missing values.";
			}

			// $pass = $this->encrypt($pass);

			if (!$errors) {
				$createUser = $this->db->prepare("INSERT INTO Users (Code,Fname,Lname,Email,Password,Confirm,Admin) VALUES (:code,:fname,:lname,:email,:pass,0,:admin)");
				$createUser->bindValue(':code',$code);
				$createUser->bindValue(':fname',$fname);
				$createUser->bindValue(':lname',$lname);
				$createUser->bindValue(':email',$email);
				$createUser->bindValue(':pass',$pass);
				$createUser->bindValue(':admin',$admin);
				$result = $createUser->execute();

				if ($result) {
					return array(
						'success' => true,
						'statusMessage' => "Account has been successfully created."
					);
				} else {
					return array(
						'success' => false,
						'statusMessage' => "There was an error creating account."
					);
				}
			} else {
				return array(
					'success' => false,
					'statusMessage' => $msg
				);
			}
		} else {
			return array(
				'success' => false,
				'statusMessage' => "You must be an admin to create an account, or settings must be set to Open Registration."
			);
		}
	}

	public function deleteUser($userCode,$uid) {
		if ($this->adminCheck($userCode)) {
			$deleteUser = $this->db->prepare("DELETE FROM Users WHERE Code=:code");
			$deleteUser->bindValue(':code',$uid);
			$result = $deleteUser->execute();
			
			if ($result) {
				return array(
					'success' => true,
					'statusMessage' => "User has been successfully removed."
				);
			} else {
				return array(
					'success' => false,
					'statusMessage' => "There was an error removing user."
				);
			}
		} else {
			return array(
				'success' => false,
				'statusMessage' => "You must be an admin to remove this user."
			);
		}
	}

	public function getUsers($userCode) {
		if ($this->adminCheck($userCode)) {
			$userLogin = $this->db->prepare("SELECT * FROM Users");
			$result = $userLogin->execute();
			$list = array();

			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				if ($row['Code'] != $this->api_member_code) {
					$list[] = array(
						"fname" => $row['Fname'],
						"lname" => $row['Lname'],
						"email" => $row['Email'],
						"admin" => ($row['Admin']?true:false),
						"active" => ($row['Confirm']?true:false),
						"superadmin" => ($row['ID']==1?true:false),
						"code"  => $row['Code']
					);
				}
			}

			return array(
				'success' => true,
				'statusMessage' => 'User list found.',
				'list' => $list
			);
		} else {
			return array(
				'success' => false,
				'statusMessage' => "You must be an admin to access users."
			);
		}
	}

	public function getUser($userCode) {
		$code = (isset($_POST['usercode']) ? $_POST['usercode']:"");
		$error = false;

		if ($code == "") {
			$error = true;
			$errorMsg = "Missing values.";
		} else if ($_SESSION['bdmembercode'] != $code) {
			$error = true;
			$errorMsg = "There is a mismatch with your account id.";
		}

		if (!$error) {
			$getUser = $this->db->prepare("SELECT * FROM Users WHERE Code=:code");
			$getUser->bindValue(':code',$userCode);
			$result = $getUser->execute();

			if ($result) {
				$row = $result->fetchArray(SQLITE3_ASSOC);

				return array(
					'success' => true,
					'statusMessage' => 'User found.',
					'user' => array(
						"fname" => $row['Fname'],
						"lname" => $row['Lname'],
						"email" => $row['Email'],
						"admin" => ($row['Admin']?true:false),
						"active" => ($row['Confirm']?true:false),
						"code"  => $row['Code']
					)
				);
			} else {
				return array(
					'success' => false,
					'statusMessage' => "User not found."
				);
			}
		} else {
			return array(
				'success' => false,
				'statusMessage' => $errorMsg
			);
		}
	}

	private function init() {
		$this->db->exec('CREATE TABLE IF NOT EXISTS Users ("ID" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL UNIQUE, "Code" VARCHAR, "Fname" VARCHAR, "Lname" VARCHAR, "Email" VARCHAR, "Password" VARCHAR, "Admin" INTEGER NOT NULL DEFAULT 0, "Confirm" INTEGER NOT NULL DEFAULT 0)');

		$prepare = $this->db->prepare("SELECT count(*) AS total FROM Users");
		$result = $prepare->execute();
		$row = $result->fetchArray(SQLITE3_ASSOC);

		if ($row['total'] == 0) {

			$results = $this->createUser($this->api_member_code,$this->config['defaultEmail'],base64_encode('admin'),1);
			
			if ($results['success']) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}
}

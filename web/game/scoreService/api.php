<?php
 	require_once("Rest.inc.php");

	
	class API extends REST {
	
		public $data = "";
		
		const DB_SERVER = "localhost";
		const DB_USER = "root";
		const DB_PASSWORD = "";
		const DB = "angularcode_customer";

		private $db = NULL;
		private $mysqli = NULL;
		public function __construct(){
			parent::__construct();				// Init parent contructor
			$this->dbConnect();					// Initiate Database connection
		}
		
		/*
		 *  Connect to Database
		*/
		private function dbConnect(){
			$this->mysqli = new mysqli(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB);
		}
		
		/*
		 * Dynmically call the method based on the query string
		 */
		public function processApi(){
			$func = strtolower(trim(str_replace("/","",$_REQUEST['x'])));
			if((int)method_exists($this,$func) > 0)
				$this->$func();
			else
				$this->response('',404); // If the method not exist with in this class "Page not found".
		}
				

		private function country(){
			/*function get_country($ip) {
			    return file_get_contents("http://ipinfo.io/{$ip}/country");
			}

			$ip = getenv('HTTP_CLIENT_IP')?:
			getenv('HTTP_X_FORWARDED_FOR')?:
			getenv('HTTP_X_FORWARDED')?:
			getenv('HTTP_FORWARDED_FOR')?:
			getenv('HTTP_FORWARDED')?:
			getenv('REMOTE_ADDR');*/

			function ip_info($ip = NULL, $purpose = "location", $deep_detect = TRUE) {
			    $output = NULL;
			    if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
			        $ip = $_SERVER["REMOTE_ADDR"];
			        if ($deep_detect) {
			            if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
			                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			            if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
			                $ip = $_SERVER['HTTP_CLIENT_IP'];
			        }
			    }
			    $purpose    = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
			    $support    = array("country", "countrycode", "state", "region", "city", "location", "address");
			    $continents = array(
			        "AF" => "Africa",
			        "AN" => "Antarctica",
			        "AS" => "Asia",
			        "EU" => "Europe",
			        "OC" => "Australia (Oceania)",
			        "NA" => "North America",
			        "SA" => "South America"
			    );
			    if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
			        $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
			        if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
			            switch ($purpose) {
			                case "location":
			                    $output = array(
			                        "city"           => @$ipdat->geoplugin_city,
			                        "state"          => @$ipdat->geoplugin_regionName,
			                        "country"        => @$ipdat->geoplugin_countryName,
			                        "country_code"   => @$ipdat->geoplugin_countryCode,
			                        "continent"      => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
			                        "continent_code" => @$ipdat->geoplugin_continentCode
			                    );
			                    break;
			                case "address":
			                    $address = array($ipdat->geoplugin_countryName);
			                    if (@strlen($ipdat->geoplugin_regionName) >= 1)
			                        $address[] = $ipdat->geoplugin_regionName;
			                    if (@strlen($ipdat->geoplugin_city) >= 1)
			                        $address[] = $ipdat->geoplugin_city;
			                    $output = implode(", ", array_reverse($address));
			                    break;
			                case "city":
			                    $output = @$ipdat->geoplugin_city;
			                    break;
			                case "state":
			                    $output = @$ipdat->geoplugin_regionName;
			                    break;
			                case "region":
			                    $output = @$ipdat->geoplugin_regionName;
			                    break;
			                case "country":
			                    $output = @$ipdat->geoplugin_countryName;
			                    break;
			                case "countrycode":
			                    $output = @$ipdat->geoplugin_countryCode;
			                    break;
			            }
			        }
			    }
			    return $output;
			}

			$this->response(ip_info("Visitor", "Country"), 200);
		}
		
		private function players(){	
			if($this->get_request_method() != "GET"){
				$this->response('',406);
			}
			$query="SELECT distinct c.id, c.score, c.name, c.land, c.date FROM highscore c order by c.score desc";
			$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

			if($r->num_rows > 0){
				$result = array();
				while($row = $r->fetch_assoc()){
					$result[] = $row;
				}
				$this->response($this->json($result), 200); // send user details
			}
			$this->response('',204);	// If no records "No Content" status
		}

		
		private function insertPlayer(){
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}

			$customer = json_decode(file_get_contents("php://input"),true);
			$column_names = array('id', 'score', 'name', 'land', 'date');
			$keys = array_keys($customer);
			$columns = '';
			$values = '';
			foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
			   if(!in_array($desired_key, $keys)) {
			   		$$desired_key = '';
				}else{
					$$desired_key = $customer[$desired_key];
				}
				$columns = $columns.$desired_key.',';
				$values = $values."'".$$desired_key."',";
			}
			$query = "INSERT INTO highscore(".trim($columns,',').") VALUES(".trim($values,',').")";
			if(!empty($customer)){
				$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
				$success = array('status' => "Success", "msg" => "Player in DB.", "data" => $customer);
				$this->response($this->json($success),200);
			}else
				$this->response('',204);	//"No Content" status
		}

		private function updatePlayer(){

			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}

			$customer = json_decode(file_get_contents("php://input"),true);
			
			$id = $customer['id'];
	
			$column_names = array('score', 'name', 'land', 'date');
			$keys = array_keys($customer['player']);

			$columns = '';
			$values = '';


			foreach($column_names as $desired_key){ 
			   if(!in_array($desired_key, $keys)) {
			   		$$desired_key = '';
				}else{
					$$desired_key = $customer['player'][$desired_key];
				}
				$columns = $columns.$desired_key."='".$$desired_key."',";
			}


			$query = "UPDATE highscore SET ".trim($columns,',')." WHERE id='$id'";
			if(!empty($customer)){
				$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
				$success = array('status' => "Success", "msg" => "Customer ".$id." Updated Successfully.", "data" => $customer);
				$this->response($this->json($success),200);
			}else
				$this->response('',204);
		}
	
		
		/*
		 *	Encode array into JSON
		*/
		private function json($data){
			if(is_array($data)){
				return json_encode($data);
			}
		}
	}
	
	// Initiiate Library
	
	$api = new API;
	$api->processApi();
?>
<?php
require_once (INSTALL_DIR."/phpMailer/class.phpmailer.php");

class Application {
		
	function __construct() {
		self::lireConstantes();
		// sorties PHP en français
		setlocale(LC_ALL, "fr_FR.utf8");
    }

	/*
	 * function lireConstantes
	 * @param 
	 * @return : retourne les constantes globales pour toutes les applis
	 */
	public static function lireConstantes() {
		// lecture des paramètres généraux dans le fichier .ini, y compris la constante "PFX"
		$constantes = parse_ini_file(INSTALL_DIR."/config.ini");
		foreach ($constantes as $key=>$value) {
			define("$key", $value);
			}

		// lecture dans la table PREFIXETATBLES."config" de la BD
		$connexion = self::connectPDO (SERVEUR, BASE, NOM, MDP);
		$sql = "SELECT parametre,valeur ";
		$sql .= "FROM ".PFX."config ";
		$resultat = $connexion->query($sql);
		if ($resultat) {
			while ($ligne = $resultat->fetch()) {
				$key = $ligne['parametre'];
				$valeur = $ligne['valeur'];
				define ("$key", $valeur);
				}
			}
			else die("config table not present");
		self::DeconnexionPDO($connexion);
	}
	
	/**
	 * suppression de tous les échappements automatiques dans le tableau passé en argument
	 * @param $tableau
	 * @return array
	 */
	private function Normaliser ($tableau) {
		foreach ($tableau as $clef => $valeur) {
			if (!is_array($valeur))
				$tableau [$clef] = stripslashes($valeur);
				else
				// appel récursif
				$tableau [$clef] = self::Normaliser($valeur);
			}
	return $tableau;
	}
	
	
	### --------------------------------------------------------------------###
	public function Normalisation() {
		// si magic_quotes est "ON",
		if (get_magic_quotes_gpc()) {
			$_POST = self::Normaliser($_POST);    // normaliser les $_POST
			$_GET = self::Normaliser($_GET);        // normaliser les $_GET
			$_REQUEST = self::Normaliser($_REQUEST);    // normaliser les $_REQUEST
			$_COOKIE = self::Normaliser($_COOKIE);    // normaliser les $_COOKIE
			}
	}

	/** 
	 * afficher proprement le contenu d'une variable précisée
	 * le programme est éventuellement interrompu si demandé
	 * @param : $data n'importe quel tableau ou variable
	 * @param boolean  $die : si l'on souhaite interrompre le programme avec le dump
	 * @return void() : dump prêt à l'affichage
	 * */
	public static function afficher($data, $die=false) {
	if (func_num_args() > 0) {
		$data = func_get_args();
		}
	echo "<pre>".print_r($data, 1)."</pre>";
	if ($die == true) die();
	}

	/***
	 * renvoie le temps écoulé depuis le déclenchement du chrono
	 * @param
	 * @return string 
	 */
	public static function chrono () {
		$temps = explode(' ', microtime());
		return $temps[0]+$temps[1];
		}
		
	/*** 
	* vider le répertoire "$dir" de tous les fichiers qu'il contient
	* @param sting $dir
	* @return void()
	*/
	public function vider ($dir) {
		// liste des fichiers sauf "." et ".."
		$listeFichiers = dirFiles ($dir);
		foreach ($listeFichiers as $unFichier) {
			@unlink ("$dir/$unFichier");
			}
		}
	
	/***
	 * Connexion à la base de données précisée
	 * @param PARAM_HOST : serveur hôte
	 * @param PARAM_BD : nom de la base de données
	 * @param PARAM_USER : nom d'utilisateur
	 * @param PARAM_PWD : mot de passe
	 * @return connexion à la BD
	 */
	public static function connectPDO ($host, $bd, $user, $mdp) {
		try {
			// indiquer que les requêtes sont transmises en UTF8
			// INDISPENSABLE POUR EVITER LES PROBLEMES DE CARACTERES ACCENTUES
			$connexion = new PDO('mysql:host='.$host.';dbname='.$bd, $user, $mdp,
								array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
			}
		catch(Exception $e)	{
			echo ("Une erreur est survenue lors de l'ouverture de la base de données");
			print_r(array($host,$bd,$user,$mdp));
			die();
		}
		return $connexion;
	}

	/***
	 * Déconnecte la base de données
	 * @param $connexion
	 * @return void()
	 */
	public static function DeconnexionPDO ($connexion) {
		$connexion = Null;
		}
	
	/**
	 * retourne un array contenant une liste des périodes de l'année scolaire
	 * @param $nbBulletins
	 * @return array
	 */
	public function listePeriodes ($nbBulletins, $debut=1) {
		return range($debut,$nbBulletins);
		}
		
	
	/***
	 * renvoie la liste des applications existantes (et, éventuellement, seulement celles qui sont activées)
	 * @param boolean $active
	 * @return array
	 */
	public function listeApplis($actives='') {
		$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
		$sql = "SELECT nom, nomLong, active ";
		$sql .= "FROM ".PFX."applications ";
		if ($actives)
			$sql .= "WHERE active ";
		$sql .= "ORDER BY ordre, LOWER(nom)";
		$resultat = $connexion->query($sql);
		$liste = array();
		while ($ligne = $resultat->fetch()) {
			$nom = $ligne['nom'];
			$nomLong = $ligne['nomLong'];
			$active = $ligne['active'];
			$liste[$nom] = array("nom"=>$nom, "nomLong"=>$nomLong, "active"=>$active);
			}
		self::DeconnexionPDO($connexion);
		return $liste;
	}
	
	/***
	 * liste de tous les droits existants dans la BD pour les différentes applications
	 * educ, prof, admin, direction,... et tous les autres statuts éventuels existants
	 * ou à venir définis dans les applis
	 * @param
	 * @return array
	 */
	
	public function listeDroits () {
		$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
		$sql = "SELECT userStatus FROM ".PFX."userStatus ORDER BY ordre";
		$resultat = $connexion->query($sql);
		$liste = array();
		if ($resultat) {
			$resultat->setFetchMode(PDO::FETCH_ASSOC);
			while ($ligne = $resultat->fetch())
				array_push($liste, $ligne['userStatus']);
			}
		self::DeconnexionPDO($connexion);
		return $liste;
	}
	
	/**
	 * retourne la liste des statuts disponibles dans l'application
	 * semblable à la fonction listeDroits() mais retourne un tableau différent
	 * à faire: supprimer listeDroits() et remplace par listeStatuts()
	 * @param
	 * @return array
	 */
	public function listeStatuts(){
		$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
		$sql = "SELECT userStatus, nomStatut ";
		$sql .= "FROM ".PFX."userStatus ORDER BY ordre";
		$resultat = $connexion->query($sql);
		$liste = array();
		if ($resultat) {
			$resultat->setFetchMode(PDO::FETCH_ASSOC);
			while ($ligne = $resultat->fetch()) {
				$status = $ligne['userStatus'];
				$liste[$status] = $ligne['nomStatut'];
				}
			}
		self::DeconnexionPDO($connexion);

		return $liste;
	}
	
	
	// --------------------------------------------------------------------
	// enregistre le statut d'activation / désactivation des applis
	function saveApplisStatus ($post) {
		// chercher la liste des applications disponibles
		$listeApplis = Application::listeApplis();
		// logout et admin ne peuvent jamais être désactivées
		unset($listeApplis['logout']);
		unset($listeApplis['admin']);
		$bilan = array();
		$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
		$nbModifications = 0;
		foreach ($listeApplis as $nom=>$details) {
			$status = isset($post[$nom])?1:0;
			$sql = "UPDATE ".PFX."applications ";
			$sql .= "SET active = '$status' WHERE nom = '$nom'";
			$resultat = $connexion->exec($sql);
			$nbModifications += $resultat;
			}
		self::DeconnexionPDO($connexion);
		return $nbModifications;
		}
		
	/**
	 * modifie le statut de l'utilisateur pour l'application donnée
	 * @param $acronyme
	 * @param $application
	 * @param $statut
	 * @return boolean
	 */
	public function changeStatut($acronyme, $application, $statut) {
		$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
		$sql = "INSERT INTO ".PFX."profsApplications ";
		$sql .= "SET acronyme='$acronyme', application='$application', userStatus='$statut' ";
		$sql .= "ON DUPLICATE KEY UPDATE userStatus='$statut' ";
		$resultat = $connexion->exec($sql);
		$ok = ($resultat > 0);
		self::DeconnexionPDO($connexion);
		return $ok;
	}
	
	/*
	 * function affecteDroitsApplications
	 * @param $usersList, $applications, $droits
	 * affectation des droits pour les applications aux utilisateurs
	 */
	function affecteDroitsApplications ($usersList, $applications, $droits) {
		if ((count($usersList)>0) && (count($applications)>0) && (count($droits)>0)) {
			$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
			$sql = "INSERT INTO ".PFX."profsApplications ";
			$sql .= "SET application = :application, userStatus= :userStatus, acronyme= :acronyme ";
			$sql .= "ON DUPLICATE KEY UPDATE userStatus= :userStatus ";
			$requete = $connexion->prepare($sql);
			$bilan = array();
			foreach ($usersList as $key=>$acronyme) {
				foreach ($applications as $uneAppli) {
					$data = array(':application'=>$uneAppli, ':userStatus'=>$droits, ':acronyme'=>$acronyme);
					$resultat = $requete->execute($data);
					if ($resultat === false) 
						$bilan[] = array("acronyme"=>$acronyme, "application"=>$application, "droit"=>$droits, "statut"=>"echec");
						else $bilan[] = array("acronyme"=>$acronyme, "application"=>$uneAppli, "droit"=>$droits, "statut"=>"OK");
					}
				}
			self::DeconnexionPDO($connexion);
			}
		return $bilan;
		}
	
	
	/*** 
	 * renvoie la liste des paramètres généraux de l'ensemble de l'application en provenance de la BD
	 * @param 
	 * @result array $listeParametres
	 */
	public function lireParametres () {
		$connexion = self::connectPDO (SERVEUR, BASE, NOM, MDP);
		$sql = "SELECT parametre, valeur, signification ";
		$sql .= "FROM ".PFX."config";
		$resultat = $connexion->query($sql);
		$listeParametres = array();
		while ($ligne = $resultat->fetch()) {
			$parametre = $ligne['parametre'];
			$listeParametres[$parametre]['valeur'] = $ligne['valeur'];
			$listeParametres[$parametre]['signification'] = $ligne['signification'];
			}
		self::DeconnexionPDO($connexion);
		return $listeParametres;
		}
		
	/***
	 * enregistre les paramètres généraux de l'application et renvoie le nombre de paramètres enregistrés
	 * @param post
	 * @result $nb 
	 */
	function saveParametres ($post) {
		$connexion = self::connectPDO (SERVEUR, BASE, NOM, MDP);
		// vérification du nom des paramètres existants dans la BD afin d'éviter
		// d'enregistrer un paramètre qui n'existerait pas
		$sql = "SELECT parametre FROM ".PFX."config";
		$resultat = $connexion->query($sql);
		$listeParametres = array();
		while ($ligne = $resultat->fetch()) {
			$listeParametres[] = addslashes($ligne['parametre']);
			}
		$resultat = 0;
		foreach ($post as $parametre => $value) {
			if (in_array($parametre, $listeParametres)) {
				$value = addslashes($value);
				$sql = "INSERT INTO ".PFX."config ";
				$sql .= "SET parametre='$parametre', valeur='$value' ";
				$sql .= "ON DUPLICATE KEY UPDATE valeur='$value'";
				$resultat += $connexion->exec($sql);
				}
			}
		self::DeconnexionPDO($connexion);
		return $resultat;
		}
	 
	/*
	 * function renvoiMdp
	 * @param $acronyme
	 * renvoi du mot de passe à l'utilisateur dont l'acronyme est fourni
	 */
	public function renvoiMdp ($acronyme) {
		if (isset($acronyme)) {
			$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
			$sql = "SELECT nom, prenom, mail FROM ".PFX."profs ";
			$sql .= "WHERE acronyme='$acronyme'";
			$resultat = $connexion->query($sql);
			if ($resultat) {
				// cet acronyme existe. On a peut-etre une adresse e-mail
				$ligne = $resultat->fetch();
				$mail = $ligne['mail'];
				if ($mail != '') {
					// génération d'un nouveau mot de passe
					$nouveauMotDePasse = self::generatePassword(8, 3);
					// encodage md5
					$md5Mdp = md5($nouveauMotDePasse);
					$sql = "UPDATE ".PFX."profs SET mdp = '$md5Mdp' ";
					$sql .= "WHERE acronyme='$acronyme'";
					$resultat = $connexion->exec($sql);
					mail($mail, PWD." ".TITREGENERAL, sprintf(NEWPWD, $nouveauMotDePasse),
						 "From: ".ROBOT."@".DOMAIN);
					mail(MAILADMIN, PWD, "$acronyme : $mail");
					echo sprintf (NEWPWDSEND, $mail);
					self::DeconnexionPDO($connexion);
					}
					else echo NOMAIL.ADMINISTRATOR;
			}
		}
		else echo USERNAME;
	}	 
	 
	
	public function listeFlashInfos ($module) {
		$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
		$sql = "SELECT * FROM ".PFX."flashInfos ";
		$sql .= "WHERE application = '$module' ";
		$sql .= "ORDER BY date DESC";
		$resultat = $connexion->query($sql);
	
		$flashInfos = array();
		if ($resultat) {
			$resultat->setFetchMode(PDO::FETCH_ASSOC);
			while ($ligne = $resultat->fetch()) {
				$flashInfos[] = $ligne;
				}
			}
		self::DeconnexionPDO($connexion);
	return $flashInfos;
	}
	
	### --------------------------------------------------------------------###
	public static function repertoireActuel () {
		$dir = array_reverse(explode("/",getcwd()));
		return $dir[0];
	}
	
	### --------------------------------------------------------------------###
	public function accesApplication ($nomApplication) {
		// vérifier que l'utilisateur est identifié pour l'application active
		return 	(
				isset($_SESSION['identite']['acronyme']) &&
				isset($_SESSION['identite']['mdp']) &&
				$_SESSION['applicationName'] == $nomApplication
				);
	}
	### --------------------------------------------------------------------###
	// Vérification de l'activation du module pour l'utilisateur actif
	public function accesModule($BASEDIR) {
		$applisAutorisees = array_keys($_SESSION['applications']);
		if (!(in_array(repertoireActuel(), $applisAutorisees)))
			header("Location: ".$BASEDIR."index.php");
			else return true;
	}
	
	
	### --------------------------------------------------------------------###
	private function generatePassword($length=9, $robustesse=0) {
		$voyelles = "aeuy";
		$consonnes = "bdghjmnpqrstvz";
		if ($robustesse & 1) {
			$consonnes .= "BDGHJLMNPQRSTVWXZ";
		}
		if ($robustesse & 2) {
			$voyelles .= "AEUY";
		}
		if ($robustesse & 4) {
			$consonnes .= "23456789";
		}
		if ($robustesse & 8) {
			$consonnes .= "@#$%";
		}
	 
		$password = "";
		$alt = time() % 2;
		for ($i = 0; $i < $length; $i++) {
			if ($alt == 1) {
				$password .= $consonnes[(rand() % strlen($consonnes))];
				$alt = 0;
			} else {
				$password .= $voyelles[(rand() % strlen($voyelles))];
				$alt = 1;
			}
		}
		return $password;
	}
	
	/**
	* convertir les dates au format usuel jj/mm/AAAA en YY-mm-dd pour MySQL
	* @param string $date date au format usuel
	* @return string date au format MySQL
	*/
	public static function dateMysql ($date) {
		$dateArray = explode("/",$date);
		$sqlArray=array_reverse($dateArray);
		$date = implode("-",$sqlArray);
		return $date;
		}
	
	/**
	* convertir les date au format MySQL vers le format usuel
	* @param string $date date au format MySQL
	* @return string date au format usuel français
	*/
	public static function datePHP ($dateMysql) {
		$dateArray = explode("-", $dateMysql);
		$phpArray = array_reverse($dateArray);
		$date = implode("/", $phpArray);
		return $date;
		}

	/**
	 * convertir les heures au format MySQL vers le format ordinaire à 24h
	 * @param string $heure l'heure à convertir
	 * @return string l'heure au format usuel
	 */
	public static function heureMySQL ($heure) {
		$heureArray = explode(":",$heure);
		$sqlArray = array_reverse($heureArray);
		$heure = implode(":",$sqlArray);
		return $heure;
		}
		
	public static function heurePHP ($heure) {
		$heureArray = explode(":",$heure);
		$sqlArray = array_reverse($heureArray);
		$heure = implode(":",$sqlArray);
		return $heure;
		}
		
		/**
		 * retourne le jour de la semaine correspondant à une date au format MySQL
		 * @param string $dataMySQL
		 * @return string
		 */
		public static function jourSemaineMySQL ($dateMySQL) {
			$timeStamp = strtotime($dateMySQL);
			return strftime("%A", $timeStamp);
		}
		
		/**
		* Fonction de conversion de date du format français (JJ/MM/AAAA) en Timestamp.
		* @param string $date Date au format français (JJ/MM/AAAA)
		* @return integer Timestamp en seconde
		* http://www.julien-breux.com/2009/02/17/fonction-php-date-francaise-vers-timestamp/
		*/
		public static function dateFR2Time($date)	{
			  list($day, $month, $year) = explode('/', $date);
			  $timestamp = mktime(0, 0, 0, $month, $day, $year);
			  return $timestamp;
			}
		
	/**
	 * Extrait la liste des fichiers présentes dans un répertoire
	 * @param $repertoire
	 * @return array un tableau de la liste des fichiers d'un répertoire sauf les fichiers ".", ".." et "index"
	 */
	public function listeFichiers ($repertoire) {
		$liste = scandir ($repertoire,0);
		$exclus = array('.','..','index.php', 'index.html');
		$liste = array_diff(scandir($repertoire, 0), $exclus);
		return $liste;
	}
	
	
	/**
	 * Recursive function to scan a directory with * scandir() *
	 *
	 * @param String $rootDir
	 * @return multi dimensional array
	 * http://php.net/manual/en/function.scandir.php
	 */
	function scanDirectories($rootDir) {
		// set filenames invisible if you want
		$invisibleFileNames = array(".", "..", "index.html", "index.php");
		// run through content of root directory
		$dirContent = scandir($rootDir);
		$allData = array();
		// file counter gets incremented for a better
		$fileCounter = 0;
		foreach($dirContent as $key => $content) {
			// filter all files not accessible
			$path = $rootDir.'/'.$content;
			if(!in_array($content, $invisibleFileNames)) {
				// if content is file & readable, add to array
				if(is_file($path) && is_readable($path)) {
					$tmpPathArray = explode("/",$path);
					// saving filename
					$allData[$fileCounter]['fileName'] = end($tmpPathArray);
					// saving while path (for better access)
					$allData[$fileCounter]['filePath'] = $path;
					// get file extension
					$filePartsTmp = explode(".", end($tmpPathArray));
					$allData[$fileCounter]['fileExt'] = end($filePartsTmp);
					// get file date
					$allData[$fileCounter]['fileDate'] = filectime($path);
					// get filesize in byte
					$allData[$fileCounter]['fileSize'] = filesize($path);
					$fileCounter++;
				// if content is a directory and readable, add path and name
				}elseif(is_dir($path) && is_readable($path)) {
					$dirNameArray = explode('/',$path);
					$allData[$path]['dirPath'] = $path;
					$allData[$path]['dirName'] = end($dirNameArray);
					// recursive callback to open new directory
					$allData[$path]['content'] = self::scanDirectories($path);
				}
			}
		}
		return $allData;
	}
	
		
	/**
	 * Établir la liste des utilisateurs (profs) sans cours
	 * @param
	 * @return array
	 */
	public function listOrphanUsers () {
		$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
		$sql = "SELECT nom,prenom, acronyme ";
		$sql .= "FROM ".PFX."profs WHERE ";
		$sql .= "acronyme NOT IN (SELECT acronyme FROM ".PFX."profsCours) ";
		$sql .= "ORDER BY REPLACE(REPLACE (nom,' ',''),'\'','')";
		$resultat = $connexion->query($sql);
	
		$users = array();
		while ($ligne = $resultat->fetch()){
			$acronyme = $ligne['acronyme'];
			$users[$acronyme] = array('nom'=>$ligne['nom'], 'prenom'=>$ligne['prenom']);
			}
		self::DeconnexionPDO($connexion);
		return $users;
		}


	/**
	 * Suppression de l'utilisateur désigné par son "acronym"
	 * @param string $acronyme
	 * @return boolean statut d'erreur: OK ou PAS OK
	 */
	function deleteUser ($acronyme) {
	   if ($acronyme == Null) die();
	   $connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
	   $sql = "DELETE FROM ".PFX."profs WHERE acronyme='$acronyme'";
	   $resultat = $connexion->exec($sql);
	   $erreur1 = ($resultat === false);
	   $sql = "DELETE FROM ".PFX."profsApplications WHERE acronyme='$acronyme'";
	   $resultat = $connexion->exec($sql);
	   $erreur2 = ($resultat === false);
	   self::DeconnexionPDO ($connexion);
	   return !($erreur1 || $erreur2);
	   }
	   
	/*
	* function derniersConnectes
	* @param $limite
	* liste des derniers utilisateurs connectés
	*/
	public function derniersConnectes($limite) {
	   $connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
	   $sql = "SELECT distinct user, id,  heure, date, ip, host FROM ".PFX."logins ";
	   $sql .= "ORDER BY id desc limit 0,$limite";
	   $resultat = $connexion->query($sql);
	   if ($resultat) {
		   $resultat->setFetchMode(PDO::FETCH_ASSOC);
		   $tableau = $resultat->fetchall();
		   }
		   else $tableau = Null;
	   self::DeconnexionPDO($connexion);
	   return $tableau;
	   }
	
	/**
	* On fournit une liste de tables; la procédure fabrique un fichiers .sql permettant la restauration des tables désignées
	* @param $post : formulaire dans lequel on a coché les noms des tables à sauvegarder
	* @return string : nom du fichier .sql.gz créé
	*/
	function backupTables($post) {
		$listeTables = array();
		// seuls les inputs dont le nom commence par "check" sont à considérer
		foreach ($post as $unItem=>$value) {
			if (strstr($unItem, "check")) {
			$data = explode("_",$unItem);
			$listeTables[] = PFX.$data[1];
			}
		}
		$nb = count($listeTables);
		$nbTotal = $this->DBnumTables();
		$listeTables = implode(" ",$listeTables);
		if ($nb < $nbTotal)
			$fileName = date("Y-m-d:Hms")."_$nb-tables.sql";
			else $fileName = date("Y-m-d:Hms")."_complet.sql";
		
		try {
		$output = exec("mysqldump -u ".NOM." --host=".SERVEUR." --password=".MDP." ".BASE." --tables $listeTables > save/$fileName") ;
		}
		catch (Exception $e) {
		die ($e->getMessage());
		}
		system("gzip save/$fileName");
		return $fileName;
		}
		
	/**
	 * retourne le nombre total de tables de la BD
	 * @param
	 * @return integer
	 */
	private function DBnumTables(){
		$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
		$sql = "SELECT count(*) as 'Tables', table_schema as 'Database' ";
		$sql .= "FROM information_schema.TABLES ";
		$sql .= "WHERE table_schema= '".BASE."' ";
		$sql .= "GROUP BY table_schema ";
		$resultat = $connexion->query($sql);
		$ligne = $resultat->fetch();
		self::DeconnexionPDO ($connexion);
		return $ligne['Tables'];
	}

	/**
	 * function lireFlashInfos
	 * @param $$module
	 * retourne tous les Flash Infos d'un module
	 */
	public function lireFlashInfos ($module) {
		$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
		$sql = "SELECT * FROM ".PFX."flashInfos ";
		$sql .= "WHERE application ='$module' ";
		$sql .= "ORDER BY date DESC, heure DESC";
		$resultat = $connexion->query($sql);
		$flashInfos=array();
		if ($resultat) {
			$resultat->setFetchMode(PDO::FETCH_ASSOC);
			while ($ligne = $resultat->fetch())
				$flashInfos[]=$ligne;
			}
		self::DeconnexionPDO ($connexion);
		return $flashInfos;
	}

	/**
	 * changement d'utilisateur en gardant les droits admins (Application)
	 * @param $acronyme
	 * @return string
	 * 
	 */
	public function changeUserAdmin ($acronyme) {
		// liste de toutes les applications avec leurs droits
		$listeApplications = $_SESSION[APPLICATION]->getApplications();
		$appliAdmin = array('admin'=>$listeApplications['admin']);
		
		// prépartion d'un nouvel utilisateur
		$toto = new user($acronyme);
		// lui conférer les droits "admin" sur ses applications
		$toto->setApplicationsAdmin();
		// lui ajouter les droits "admin" sur l'admin
		$toto->addAppli($appliAdmin);
		// l'affecter à la session 
		$_SESSION[APPLICATION] = $toto;
		$qui = $_SESSION[APPLICATION]->identite();
		return $qui['prenom']." ".$qui['nom'].": ".$qui['acronyme'];
	}


	/**
	 * renvoie la liste des utilisateurs qui disposent du statut global $status dans l'application
	 * permet de retrouver tous les "admins", par exemple.
	 * 
	 * @param $status : string
	 * @return array
	 * 
	 */
	public function getUserByStatus ($status) {
		$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
		$sql = "SELECT * FROM ".PFX."profs ";
		$sql .= "WHERE statut = '$status' ";
		$sql .= "ORDER BY nom, prenom ";
		$resultat = $connexion->query($sql);
		$listeUsers = array();
		if ($resultat) {
			$resultat->setFetchMode(PDO::FETCH_ASSOC);
			$listeUsers = $resultat->fetchAll();
			}
		self::DeconnexionPDO ($connexion);
		return $listeUsers;
		}

	/* 
	 * function lastAccess 
	 * 
	 * @param $user		nom de l'utilisateur concerné
	 * @param $nombre  nombre d'accès à traiter
	 * @param $from		nombre de lignes à laisser tomber en début
	 * @return array : liste des derniers accès à l'application
	 * 
	 * */
	public function lastAccess ($from, $nombre, $user) {
		$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
		$sql = "SELECT ip,host,date,DATE_FORMAT(heure,'%H:%i') as heure ";
		$sql .= "FROM ".PFX."logins ";
		$sql .= "WHERE user='$user' ";
		$sql .= "ORDER BY date DESC,heure DESC limit $from,$nombre ";
		$resultat = $connexion->query($sql);
		$acces = array();
		if ($resultat) {
			$resultat->setFetchMode(PDO::FETCH_ASSOC);
			while ($ligne = $resultat->fetch()) {
				$ligne['date'] = Application::datePHP($ligne['date']);
				$acces[] = $ligne;
				}
			}
		Application::deconnexionPDO($connexion);
		return $acces;
		}

	/** 
	 * function bannedIP 
	 * 
	 * @param $ip   	adresse IP
	 * 
	 * retourne "vrai" si adresse IP bannie
	 * */
	public function bannedIP ($ip) {
		$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
		$sql = "SELECT ip ";
		$sql .= "FROM ".PFX."banIP ";
		$sql .= "WHERE ip='$ip' ";
		$resultat = $connexion->query($sql);
		$listeIP = array();
		if ($resultat) {
			$resultat->setFetchMode(PDO::FETCH_ASSOC);
			while ($ligne = $resultat->fetch()) 
				$listeIP[] = $ligne['ip'];
			}
		Application::deconnexionPDO($connexion);
		return (in_array($ip, $listeIP));
		}

	/* 
	 * function dirFiles
	 * @param $dir : répertoire dont on veut obtenir la liste du contenu
	 * 
	 * renvoie la liste de tous les fichiers d'un répertoire
	 * */
	public function dirFiles ($dir) {
		$listeFichiers = array();
		if ($handle = @opendir("$dir")) {
			while (false !== ($file = readdir($handle))) {
				if (($file != '.') && ($file != '..'))
					$listeFichiers[] = $file;
				}
			closedir($handle);			
			}
		return $listeFichiers;
		}


	/***
	 * crée un fichier zippé à partir de tous les fichiers qui se trouvent dans le répertoire désigné
	 * 
	 * @param $dir : répertoire où se trouvent les fichiers à zipper et le fichier à enregistrer
	 * @param $filename : nom du fichier à créer
	 */
	function zipFiles ($dir, $filename) {
		$zip = new ZipArchive();
		if ($zip->open("$dir/$filename", ZIPARCHIVE::CREATE)!==TRUE) {
			exit("Impossible d'ouvrir <$filename>\n");
			}
		$listeFichiers = self::dirFiles($dir);
		foreach ($listeFichiers as $unFichier) {
			$zip->addFile("$dir/$unFichier");
			}
		$zip->close();
		}
		
	function zipFilesNiveau ($dir, $niveau) {
		$zip = new ZipArchive();
		if ($zip->open("$dir/niveau_$niveau.zip", ZIPARCHIVE::CREATE)!==TRUE) {
			exit("Impossible d'ouvrir <niveau_$niveau.zip>\n");
			}
		$listeFichiers = 	$this->dirFiles($dir);
		foreach ($listeFichiers as $unFichier) {
			if (substr($unFichier,0,1) == $niveau) 
				$zip->addFile("$dir/$unFichier");
			}
		$zip->close();
		}
		
	/* 
	 * function checkIP 
	 * @param $ip	: adresse IP
	 * @param $user	: nom de l'utilisateur
	 * renvoie "true" si l'adresse IP est déjà connue dans la table des logins pour cet utilisateur
	 * */ 
	 public function checkIP ($ip, $user){
		$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
		$sql = "SELECT COUNT(*) AS nb ";
		$sql .= "FROM ".PFX."logins ";
		$sql .= "WHERE ip='$ip' AND UPPER(user)='$user' ";
		$sql .= "ORDER BY date DESC, heure DESC";
		$resultat = $connexion->query($sql);
		$nb = 0;
		if ($resultat) {
			$ligne = $resultat->fetch();
			$nb = $ligne['nb'];
			}
		Application::deconnexionPDO($connexion);
		return $nb;
		}

	/** 
	 * function mailAlerte
	 * @param $user	: paramètres utilisateurs
	 * @param $type	: type d'alerte
	 * 
	 * Envoie un mail d'alerte à l'utilisateur et aux admins
	 * 
	 */
	public function mailAlerte($user, $type, $data=Null){
		// liste des mails des administrateurs
		$listeMailing = $this->getUserByStatus('admin');
		// userdata
		$identification = $user->identification();
		$ip = $identification['ip'];
		$hostname = $identification['hostname'];
		$acronyme = $user->getAcronyme();
		$userMail = $user->getMail();
		
		$jSemaine = strftime('%A');
		$date = date("d/m/Y");
		$heure = date("H:i");
		
		switch ($type) {
			case 'mdp':
				$sujet = sprintf(ERREURLOGIN, TITRECOURT);			
				$texteAdmin = sprintf(ERREURLOGINADMIN, $acronyme, $data['mdp'], $jSemaine, $date, $heure, $ip, $hostname, $userMail);
				if ($user->userExists($acronyme)) {
					$texteUser = sprintf(ERREURLOGINUSER, $acronyme, $data['mdp'], $jSemaine, $date, $heure, TITRECOURT, $ip, $hostname);
				}
				break;
			case 'newIP':
				$sujet = sprintf(NOUVELLEIP, TITRECOURT);
				$texteAdmin = sprintf(NOUVELLEIPADMIN, $ip, $hostname, $acronyme, $jSemaine, $date, $heure, $userMail);
				if ($user->userExists($acronyme)) 
					$texteUser = sprintf(NOUVELLEIPUSER, $ip, $hostname, $jSemaine, $date, $heure, TITRECOURT);
				break;
			default:
				//don't care
				break;
			}
		$mail = new PHPmailer(); 
		$mail->IsHTML(true);
		$mail->CharSet = 'UTF-8';
		$mail->From=MAILADMIN;
		$mail->FromName=ADMINNAME;
		$envoiMail = true;
		// avertir les administrateurs
		foreach ($listeMailing as $admin) {
			$mail->AddAddress($admin['mail']); 
			$mail->Subject=$sujet; 
			$mail->Body=$texteAdmin; 
			}
		if(!$mail->Send())
			$envoiMail = false;
			// echo $mail->ErrorInfo; 
		// prévenir l'utilisateur
			if ($userMail) {
				$mail->ClearAddresses();
				$mail->AddAddress($userMail);
				$mail->Body=$texteUser;
				$mail->Send();
				$envoiMail = false;
				}
		return $envoiMail;
		}

	/**
	 * Suppression des anciens élèves dans toutes les tables qui seront indiquées dans $post
	 * la liste des élèves provient du fichier CSV uploadé sous forme de $table
	 * @param array $post : contient la liste des tables sélectionnées qui contiennent un champ "matricule"
	 * @param   $table: nom du fichier CSV (transformé en array dans la fonction)
	 * @return  array : statistiques sur les effacements
	 */
	public function suppressionAnciens ($post, $tableEleves) {
		// former la liste des matricules des élèves
		$tableau = $this->csv2array($tableEleves);
		$listeEleves = $this->tableau2listeEleves($tableau);
		// récupérer les noms des tables sélectionnées depuis les noms des champs dans $post
		$listeTables = array();
		foreach ($post as $unChamp=>$wtf) {
			if (substr($unChamp,0,6) == 'table#') {
				$uneTable = explode('#', $unChamp);
				$listeTables[] = $uneTable[1];
				}
			}
		// suppressions dans la BD
		$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
		$resultat=0;
		foreach ($listeTables as $uneTable) {
			foreach ($listeEleves as $matricule) {
				$sql = "DELETE FROM $uneTable WHERE matricule = '$matricule' ";
				// effacement de la photo
				@unlink(INSTALL_DIR."/photos/$matricule.jpg");
				$resultat += $connexion->exec($sql);
				}
			}
		self::DeconnexionPDO($connexion);
		return array(
					'nbEleves'=>count($listeEleves),
					'nbTables'=>count($listeTables),
					'nbSuppr'=>$resultat
					);
	}

	/***
	 * recherche les éventuels hiatus entre la table importée et la structure de la base de données
	 * @param $entete
	 * @param array $champs : liste des champs à trouver
	 *
	 * @return array: éléments de l'entete du fichier CSV qui ne figurent pas dans la table de la BD et inversement
	 */
	public function hiatus ($entete, $champs) {
		$hiatus1 = array_diff($entete, $champs);
		$hiatus2 = array_diff($champs,$entete);
		$lesProblemes = array();
		if ($hiatus1 || $hiatus2) {
			foreach ($hiatus1 as $unProbleme)
				$lesProblemes[0][] = $unProbleme;
			foreach ($hiatus2 as $unProbleme)
				$lesProblemes[1][] = $unProbleme;
			}
	return $lesProblemes;
		}

	/**
	 * transforme un fichier .csv uploadé en un array()
	 *
	 * @param string $fileName : nom du fichier
	 * @return $aarray
	 */
	public function csv2array ($fileName) {
	$handle = fopen("$fileName.csv","r");
	$tableau = array();
	while (($data = fgetcsv($handle,0)) !== FALSE) {
		$tableau[] = $data;
		}
	fclose($handle);
	return $tableau;
	}
		
	/**
	 * liste de toutes les tables qui présentent un champ nommé "matricule"
	 * destinée à la suppression des anciens élèves dans toutes les tables de la BD
	 *
	 * @param
	 * @return array
	 */
	function listeTablesAvecChamp ($champ){
		$connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
		// liste de toutes les tables
		$sql = "SHOW TABLES FROM ".BASE." LIKE '".PFX."%'";
		$resultat = $connexion->query($sql);
		$listeTables = array();
		if ($resultat) 
			while ($ligne = $resultat->fetch()) 
				$listeTables[] = current($ligne);
		$n=0;
		foreach ($listeTables as $uneTable) {
			$sql = "DESCRIBE $uneTable";		
			$resultat = $connexion->query($sql);
			$found = false;
			while (!$found && $ligne = $resultat->fetch()) {
				$found = ($ligne['Field'] == $champ);
				}
			if (!$found)
				unset($listeTables[$n]);
			$n++;
			}
		Application::DeconnexionPDO($connexion);
		return $listeTables;
	}
		
	/**
	 * fournit la liste des tables pour l'application donnée
	 * si pas d'application précisée, donne la liste de toutes les tables
	 * cette liste des tables doit, elle-même, figurer dans une table
	 * de l'application 'admin'
	 * @param $base, $application
	  */
	function listeTables($base, $application=Null) {
		$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
		$sql = "SELECT application, nomTable FROM ".PFX."appliTables ";
		if ($application) $sql .= "WHERE application = '$application'";
		try {
			$resultat = $connexion->query($sql);
			}
			catch(PDOException $e){
				die ($e->getMessage());
			}
		$listeTables = array();
		if ($resultat) {
			$resultat->setFetchMode(PDO::FETCH_ASSOC);
			$listeTables = array();
			while ($ligne = $resultat->fetch()) {
				$application = $ligne['application'];
				$listeTables[$application][] = $ligne['nomTable'];
				}
			}
		self::DeconnexionPDO ($connexion);
		return $listeTables;
	}
	/**
	* function SQLtableFields2array
	* @param $table
	* renvoie un tableau indiquant les noms des champs de la table MySQL indiquée
	*/
	public static function SQLtableFields2array ($table) {
	   $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
	   // recherche des noms des champs dans la table visée
	   $sql = "SHOW FULL COLUMNS FROM ".PFX."$table";
	   $resultat = $connexion->query($sql);
	   $champs = array();
	   if ($resultat) {
		   $resultat->setFetchMode(PDO::FETCH_ASSOC);
		   $champs = $resultat->fetchall();
		   }
	   Application::DeconnexionPDO ($connexion);
	   return $champs;
	   }
	   
	/**
	 * Enregistrement d'un fichier CSV dans la table MySQL correspondante
	 * Le contenu du fichier CSV a été préalablement vérifié et validé à l'écran
	 * 
	 * @param $table
	 * @return array : 'erreurs'=>nbErreurs, 'ajouts'=>nbAjouts
	 */
	function CSV2MySQL ($table) {
		$tableauCSV = $this->csv2array($table);
		// isolement de la première ligne du tableau qui contient les noms des champs
		// pour la requête INSERT
		$champsInsert = array_shift($tableauCSV);
		// détermination des clefs primaires de la table
		$primKeys = $this->tablePrimKeys($table);
		// détermination des champs pour la requête UPDATE
		$champsUpdate = array_diff($champsInsert, $primKeys);
	
		$sql = "INSERT INTO ".PFX."$table (".implode(',', $champsInsert).") VALUES (";
		$valuesInsert=array();
		foreach ($champsInsert as $unChamp) 
			$valuesInsert[] = ":".$unChamp;
		$sql .= implode(',',$valuesInsert).") ";
		$valuesUpdate = array();
		foreach ($champsUpdate as $unChamp) 
			 $valuesUpdate[]= "$unChamp=:$unChamp";

		// si tous les champs sont des clés primaires, il n'y a pas d'Update possible
		if ($valuesUpdate) $sql .= "ON DUPLICATE KEY UPDATE ".implode(',',$valuesUpdate);
		$connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
		$connexion->beginTransaction();
		$requete = $connexion->prepare($sql);
		$ajouts = 0; $erreurs = 0;
		foreach ($tableauCSV as $uneLigne) {
			$insert = array_combine($champsInsert,$uneLigne);
			if ($requete->execute($insert))
				$ajouts++;
				else $erreurs++;
			}
		$connexion->commit();
		Application::DeconnexionPDO($connexion);
		return array("erreurs"=>$erreurs,"ajouts"=>$ajouts);
	}

	/**
	 * function newPasswdAssign 
	 *
	 * @param array $table : liste des élèves dont il faut réinitialiser le pwd
	 * @return array : liste des erreurs et des réussites
	 */
	public function newPasswdAssign ($table) {
	$tableauCSV = self::csv2array($table);
	$entete = array_shift($tableauCSV);
	$erreurs = 0; $ajouts = 0;
	$tousEleves = self::listeTousEleves();
	$connexion = self::connectPDO(SERVEUR, BASE, NOM, MDP);
	foreach ($tableauCSV as $unEleve) {
		$matricule = $unEleve[0];
		$passwd = $unEleve[2];
		$eleve = $tousEleves[$matricule];
		$nom = $tousEleves[$matricule]['nom'];
		$prenom = $tousEleves[$matricule]['prenom'];
		$username = self::username($matricule, $nom, $prenom);
		$sql = "INSERT INTO ".PFX."passwd ";
		$sql .= "SET matricule='$matricule',passwd='$passwd', user='$username' ";
		// en cas de doublon, seul le "mot de passe" est modifié
		$sql .= "ON DUPLICATE KEY UPDATE passwd='$passwd' ";
		$resultat = $connexion->exec($sql);
		if ($resultat === false)
			$erreurs++;
			else $ajouts++;
		}
	self::DeconnexionPDO($connexion);
	return array("erreurs"=>$erreurs, "ajouts"=>$ajouts);
	}

	public static function stripAccents($string){
	return strtr($string,'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ',
		'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
		}
		
	private function stripIndesirables ($string) {
		$indesirables = array(" ", "-", "'");
		$string = str_replace($indesirables,"",$string);
		return $string;
		}
		
	/**
	 * attribue un nom d'utilisateur à un élèves première lettre du prénom, 7 lettres du nom + matricule
	 * @param $matricules
	 * @param $nom
	 * @param $prenom
	 * @return string
	 */
	function userName ($matricule, $nom, $prenom) {
		$nom = strtolower(self::stripIndesirables(self::stripAccents($nom)));
		$prenom = strtolower(self::stripIndesirables(self::stripAccents($prenom)));
		$username = substr($prenom,0,1).substr($nom,0, 7).$matricule;
		return $username;
		}

	/**
	 * retourne le nom des différents champs d'une table provenant de la BD
	 * @param $table
	 * @return array
	 */
	function nomsChampsBD($champs) {
		$nomChamps = array();
		foreach ($champs as $unChamp) 
			$nomChamps[] = $unChamp['Field'];
		return $nomChamps;
	}
		
	/**
	 * function tablePrimKeys
	 * retourne les noms des champs qui sont Primary keys
	 * @param $table
	 */
	function tablePrimKeys ($table) {
		$connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
		$sql = "SHOW KEYS FROM ".PFX."$table WHERE Key_name = 'PRIMARY' ";
		$resultat = $connexion->query($sql);
		$resultat->setFetchMode(PDO::FETCH_ASSOC);
		if ($resultat) {
			// la colonne 4 contient les noms des champs "PRIMARY"
			$keys = $resultat->fetchall(PDO::FETCH_COLUMN, 4);
			}
		Application::DeconnexionPDO($connexion);
		return $keys;
		}

	/**
	 * retourn le type MIME du fichier dont on fournit le nom
	 * @param $file
	 * @return string
	 */
	public function checkFileType($fileName) {
		$finfo = finfo_open(FILEINFO_MIME);
		return (finfo_file($finfo, $fileName));
	}

	/**
	 * function SQLtable2array
	 * @param string $table : nom de la table à convertir en array
	 * @return $tableau
	 */
	function SQLtable2array ($table) {
		$connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
		$sql = "SELECT * FROM ".PFX."$table";
		$resultat = $connexion->query($sql);
		$tableau = array();
		if ($resultat) {
			$resultat->setFetchMode(PDO::FETCH_ASSOC);
			$tableau = $resultat->fetchall();
			}
		Application::DeconnexionPDO ($connexion);
		return $tableau;
	}
	
	/**
	 * produit un tableau de la liste des élèves provenant d'un fichier CSV transformé en array
	 *
	 * @param array $tableau
	 * @return $array : liste des matricules des élèves
	 */
	function tableau2listeEleves($tableau) {
		$entete = array_shift($tableau);
		if ($entete[0] != 'matricule') die('tableau mal formé');
		$listeMatricules = array();
		foreach ($tableau as $uneLigne) {
			$listeMatricules[] = $uneLigne[0];
		}
		return $listeMatricules;
	}
}
?>

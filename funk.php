<?php

function connect_db(){
    global $connection;
    $host="localhost";
    $user="test";
    $pass="t3st3r123";
    $db="test";
    $connection = mysqli_connect($host, $user, $pass, $db) or die("ei saa ühendust mootoriga- ".mysqli_error());
    mysqli_query($connection, "SET CHARACTER SET UTF8") or die("Ei saanud baasi utf-8-sse - ".mysqli_error($connection));
}

function logi(){
    // siia on vaja funktsionaalsust (13. nädalal)
    global $connection;
    $errors =array();

    if(isset($_SESSION["user"])){
        header("Location: ?page=loomad");
    } else {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (empty($_POST["user"]) || empty($_POST["pass"])) {
                if (empty($_POST["user"])) {
                    $errors[] = "Fill in username!";
                }
                if (empty($POST["pass"])) {
                    $errors[] = "Please enter your password!";
                }

            } else {
                $username = mysqli_real_escape_string($connection, $_POST["user"]);
                $password = mysqli_real_escape_string($connection, $_POST["pass"]);
                $query = "SELECT roll FROM mtseljab_kylastajad WHERE username='".$username."' AND passw=sha1('".$password."')";
                $result = mysqli_query($connection, $query) or die("Ei saanud baasi utf-8-sse - ".mysqli_error($connection));
                $row = mysqli_fetch_assoc($result);
                if ($row) {
                    $_SESSION["user"] = $_POST["user"];
                    $_SESSION["role"] = $row["roll"];
                    header("Location: ?page=loomad");
                } else {
                    header("Location: ?page=login");
                }
            }
        }
    }

    include_once('views/login.html');
}


function logout(){
    $_SESSION=array();
    session_destroy();
    header("Location: ?");
}

function kuva_puurid(){
    // siia on vaja funktsionaalsust
    global $connection;
    $puurid = array();


    if(empty($_SESSION["user"])) {
        header("Location: ?page=login");
    } else {
        $query = "SELECT DISTINCT puur FROM loomaaed_mtseljab ORDER BY puur ASC ";
        $result = mysqli_query($connection, $query) or die("$query - " . mysqli_error($connection));

        if (mysqli_num_rows($result) > 0) {
            // output data of each row
            while ($puurinumbrid = mysqli_fetch_assoc($result)) {
                $result_loomad = mysqli_query($connection, "SELECT * FROM loomaaed_mtseljab WHERE  puur=" . $puurinumbrid['puur']);
                while ($loomarida = mysqli_fetch_assoc($result_loomad)) {
                    $puurid[$puurinumbrid['puur']][] = $loomarida;
                }
            }
        }
    }

    mysqli_close($connection);
    mysqli_free_result($result);
    include_once('views/puurid.html');

}

function lisa(){
    // siia on vaja funktsionaalsust (13. nädalal)
    global $connection;
    $errors = array();

    if(empty($_SESSION["user"])){
        header("Location: ?page=login");
    } else if ($_SESSION["role"] == "admin"){

        if ($_SERVER["REQUEST_METHOD"]=="POST"){
            if(empty($_POST["nimi"]) || empty($_POST["puur"])){
                if (empty($_POST["nimi"])){
                    $errors[] = "Fill in name!";
                } if (empty($_POST["puur"])){
                    $errors[] = "Please enter cage number!";
                }
            } else {
                upload('liik');
                $nimi = mysqli_real_escape_string ($connection, $_POST["nimi"]);
                $puur = mysqli_real_escape_string ($connection, $_POST["puur"]);
                $liik = mysqli_real_escape_string ($connection, "pildid/".$_FILES["liik"]["name"]);
                $query = "INSERT INTO loomaaed_mtseljab (nimi, puur, liik) VALUES ('$nimi','$puur','$liik')";
                $result = mysqli_query($connection, $query) or die ("$query - ".mysqli_error($connection));
                $row = mysqli_insert_id($connection);
                if ($row){
                    header("Location: ?page=loomad");
                } else {
                    header("Location: ?page=loomavorm");
                }
            }
        }
    } else header ("Location: ?page=loomaaed.php");

    include_once('views/loomavorm.html');

}

function upload($name){
    $allowedExts = array("jpg", "jpeg", "gif", "png");
    $allowedTypes = array("image/gif", "image/jpeg", "image/png","image/pjpeg");
    $extension = end(explode(".", $_FILES[$name]["name"]));

    if ( in_array($_FILES[$name]["type"], $allowedTypes)
        && ($_FILES[$name]["size"] < 100000)
        && in_array($extension, $allowedExts)) {
        // fail õiget tüüpi ja suurusega
        if ($_FILES[$name]["error"] > 0) {
            $_SESSION['notices'][]= "Return Code: " . $_FILES[$name]["error"];
            return "";
        } else {
            // vigu ei ole
            if (file_exists("pildid/" . $_FILES[$name]["name"])) {
                // fail olemas ära uuesti lae, tagasta failinimi
                $_SESSION['notices'][]= $_FILES[$name]["name"] . " juba eksisteerib. ";
                return "pildid/" .$_FILES[$name]["name"];
            } else {
                // kõik ok, aseta pilt
                move_uploaded_file($_FILES[$name]["tmp_name"], "pildid/" . $_FILES[$name]["name"]);
                return "pildid/" .$_FILES[$name]["name"];
            }
        }
    } else {
        return "";
    }
}

function hangi_loom($id){
    global $connection;

    $query = "SELECT id, nimi, puur, liik FROM loomaaed_mtseljab where ID= '".$id."' " ;
    $result = mysqli_query($connection, $query)or die("$query - ".mysqli_error($connection));;
    if ($result){
        $loom = mysqli_fetch_assoc($result);
        return $loom;
    } else {
        header("Location: ?page=loomaaed.php");
    }

}

function muuda(){
    // check if a user is logged on
    if (empty($_SESSION["user"])){
        header("Location: ?page=login");
    }
    // check if logged user is admin
    if ($_SESSION["role"]!=="admin"){
        header("Location: ?page=loomaaed.php");
    }

    echo("ID from GET array is ".$_GET["id"]."\n");
    if ($_SERVER["REQUEST_METHOD"] == "GET"){
        $id = $_GET["id"];
        $loom = hangi_loom($id);
        var_dump($loom);

    } else if ($_SERVER["REQUEST_METHOD"] == "POST"){
        global $connection;
        $errors=array();

        if(empty($_POST["nimi"])) {
            $errors[]="looma nimi puudu!";
        }

        if(empty($_POST["puur"])) {
            $errors[]="puurinr puudu!";
        }

        if (empty($errors)) {
            $id = mysqli_real_escape_string($connection, $_POST["id"]);
            $name = mysqli_real_escape_string($connection, $_POST["nimi"]);
            $cage = mysqli_real_escape_string($connection, $_POST["puur"]);
            $query = "UPDATE loomaaed_mtseljab SET nimi =\"".$name."\" , puur=".$cage." WHERE id=".$id;
            $result = mysqli_query($connection, $query);
            $id = mysqli_insert_id($connection);
            if ($id){
                header("Location: ?page=loomad");
            } else {
                header("Location: ?page=muuda");
            }
            exit(0);
        }
    } else {
        header("Location: ?page=loomad");
    }

    include_once('views/editvorm.html');
}

?>
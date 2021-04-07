<?php session_start(); ?>

<?php error_reporting(E_ALL); ?>

<?php
require_once('connection.php');
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-authenticate.php');

global $lang_id;
$lang = ICL_LANGUAGE_CODE;
if ($lang == 'en_US' || $lang == 'en_GB' || $lang == 'en') {
    $lang_id = 'EN';
} else {
    $lang_id = 'ES';
}

// INI - Cristian - 20/05/19 - validamos si hay nuevo usuario para darlo de ALTA
$current_user = wp_get_current_user();
$user_id = get_current_user_id();
$new_user_val = mysqli_query($con, "SELECT 1 FROM `TE_USERS` WHERE `USER_ID`='$user_id'");
$new_user = mysqli_num_rows($new_user_val);

// JFS - 01/07/2020 - guardar la fecha del ultimo login
$last_login_val = mysqli_query($con, "SELECT * FROM `TE_USERS` WHERE `USER_ID`='$user_id'");
$last_login = mysqli_fetch_assoc($last_login_val)['LAST_LOGIN'];

// JFS - 01/09/2020 - se tiene que crear de nuevo el usuario si no existeº
// INI - CGM - 12/06/2020 - no creamos usuario si no existe, redirigimos a pago
if ($new_user == 0) {
    //if($new_user == 0 && $lang_id == 'ES') {
    $sql = "INSERT INTO TE_USERS (user_id, `USER_NAME`, `EMAIL`,`GROUP_ID`, `TYPE_USER`, `FINISH_TRIAL`,`ACTIVE`,`FIRST_LOGIN`,`TRIAL`)
		 VALUES ( '$user_id', '$current_user->user_login', '$current_user->user_email', NULL, 'F', NULL, 1, CURRENT_TIMESTAMP,'N')";
    $con->query($sql);
    //echo "<script>window.top.location.href = window.top.location.href + \"../\" + \"/planes/\"; </script>";
    //exit();
    //}
    //else if($new_user == 0 && $lang_id == 'EN') {
    //	echo "<script>window.top.location.href = window.top.location.href + \"../en\" + \"/plans/\"; </script>";
    // FIN - CGM - 12/06/2020 - no creamos usuario si no existe, redirigimos a pago
} else {
    $sql = "UPDATE TE_USERS SET `LAST_LOGIN`= CURRENT_TIMESTAMP WHERE user_id= '$user_id'";
    $con->query($sql);
}

if ($user_id == 0) {

    die("Unable to connect");
}

// FIN - Cristian - 20/05/19 - validamos si hay nuevo usuario para darlo de ALTA
function encodes($text)
{
    if ($GLOBALS['lang_id'] == 'ES') {
        //$text = htmlentities($text, ENT_QUOTES, "ISO-8859-1");
        $text = htmlspecialchars($text, ENT_NOQUOTES, 'ISO-8859-1');
        $text = html_entity_decode($text);
        return $text;
    } else {

        return $text;
    }
}
?>

<?php
// Update definition language
function updateDefLang($user_id, $lang_id, $con)
{
    $query = mysqli_query($con, "UPDATE session_strategy_definition set `lang_id` = '$lang_id' WHERE user_id = $user_id");
}

// Get the strategy definition
function getStratDef($user_id, $con)
{
    // Get session id
    $sesion_idQuery = mysqli_query($con, "SELECT sesion_id FROM session_strategy WHERE user_id = $user_id");
    $sesion_id = mysqli_fetch_assoc($sesion_idQuery)['sesion_id'];

    // JFS - 10/12/2020 - validamos si existe el registro en session_strategy_definition
    $contQuery = mysqli_query($con, "SELECT count(*) as contador FROM session_strategy_definition WHERE sesion_id = $sesion_id");
    $count = mysqli_fetch_assoc($contQuery)['contador'];

    if ($count == 0) {
        $sql = "INSERT INTO session_strategy_definition (`user_id`, `definition_text`, `sesion_id`, `estatus`, `lang_id`) values ($user_id, ' ', $sesion_id, 'F', '$lang_id')";
        $con->query($sql);
    }

    // Ejecutar proceso
    $statment_procedure = mysqli_prepare($con, "CALL ESTRUCT_LEER_CADENA(?)");
    mysqli_stmt_bind_param($statment_procedure, 'i', $sesion_id);
    mysqli_stmt_execute($statment_procedure);
    mysqli_stmt_close($statment_procedure);

    // Get definition in txt
    $stratDefQuery = mysqli_query($con, "SELECT definition_text FROM session_strategy_definition WHERE sesion_id = $sesion_id");
    $strarDef = mysqli_fetch_assoc($stratDefQuery)['definition_text'];
    return $strarDef;
}
?>

<?php
// Si el usuario ha hecho click en algun candado de la interfaz al ser FREE, se le suma 1 al contador
if (isset($_POST['numlock']) && $_POST['numlock']) {
    $sql = "UPDATE TE_USERS set `count_lock` = `count_lock` + 1 WHERE user_id = '$user_id'";
    $con->query($sql);
}
?>

<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') :

    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'previousID') !== false) {
            unset($_SESSION[$key]);
        }
    }

endif;
?>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST') : ?>

    <?php
    if ($_GET['action'] === 'save_data') {
        $user_id = get_current_user_id();
        $open_scenario = $_POST['OPEN'];
        $modify_scenario = $_POST['MODIFY'];
        $close_scenario = $_POST['CLOSE'];

        // saving data only when there is atleast single element
        if ($open_scenario == null && $modify_scenario == null && $close_scenario == null) {
            $response = "";
        } else {
            $userAlready = $con->query("SELECT * FROM session_strategy WHERE user_id = '$user_id'");

            if (mysqli_num_rows($userAlready) > 0) {
                $sql = "UPDATE session_strategy set `open_scenario`='$open_scenario', `close_scenario`= '$close_scenario', `modify_scenario` = '$modify_scenario' WHERE user_id = '$user_id' ";
                $con->query($sql);
            } else {
                $sql = "INSERT INTO session_strategy (`user_id`, `open_scenario`, `close_scenario`, `modify_scenario`) VALUES ( '$user_id', '$open_scenario', '$close_scenario', '$modify_scenario' );";
            }
            $response = $_POST;
            $con->query($sql);
            var_dump($response);
        }
    } else if ($_GET['action'] === 'reset_strategy') {
        $user_id = get_current_user_id();
        $sql = "UPDATE session_strategy set
                                        `strategy_id` = null
                                        , `origin` = null
                                        , `open_scenario`= null
                                        , `close_scenario`= null
                                        , `modify_scenario` = null
                WHERE user_id = '$user_id' ";
        $con->query($sql);
        echo json_encode(['done' => true]);
    } else if ($_GET['action'] == 'cancel_validation') {
        $session_validation_id = $_POST['session_validation_id'];
        $res = $con->query("UPDATE session_validation SET estatus = 'X', result_report = 'Cancelado' WHERE session_validation_id = $session_validation_id ");
    } else if ($_GET['action'] == 'validate_data') {
        $user_id = get_current_user_id();

        // $res = $con->query('SELECT TYPE_USER FROM TE_USERS WHERE USER_ID = '.$con->escape_string($user_id));
        $res = $con->query("SELECT TYPE_USER FROM TE_USERS WHERE USER_ID = " . $user_id . "");
        $type_user = mysqli_fetch_row($res)[0];
        $res->free();

        if ($type_user != 'P' && $type_user != 'A' && $type_user != 'T' && $type_user != 'F') {
            echo $type_user;
            // $res->free();

            // header("Location: " . get_option( 'siteurl' ) . "/autotrading-services-vp/", true, 301);

            //exit();

            // die("no permitido");

            echo 'no permitido';
        } else {
            // $res->free();
            $balance = $_POST['balance'];
            $currency = $_POST['currency'];
            $ticker = $_POST['ticker'];
            $timeFrame = $_POST['time_frame'];
            $start_time = $_POST['start-date'];
            $end_time = $_POST['end-date'];
            $query = mysqli_query($con, "insert into alarma (mensaje) values ('$start_time')");

            // $session_strategy_id = $_POST['session_strategy_id'];
            $status = 'N';
            $fetch_session_id = mysqli_query($con, "SELECT * FROM session_strategy WHERE user_id = " . $user_id . "");
            $session_strategy_id = mysqli_fetch_assoc($fetch_session_id)['sesion_id'];
            // INI - CGM - 19/05/2020 - congelamos peticiones anteriores

            //$sql_cong = "UPDATE session_validation SET `estatus`= 'X' WHERE user_id = $user_id AND `estatus` IN('N','P','W')";
            $query = mysqli_query($con, "UPDATE `session_validation` SET `estatus`= 'X' WHERE `session_validation`.user_id = $user_id AND `session_validation`.`estatus` IN('N','P','W') AND `session_validation`.user_id IN(SELECT `TE_USERS`.user_id FROM `TE_USERS` where `TE_USERS`.user_id = $user_id AND `TE_USERS`.type_user <> 'A')");

            //$con->query($sql_cong);

            // FIN - CGM - 19/05/2020 - congelamos peticiones anteriores
            $sql = "INSERT INTO session_validation (`user_id`, `session_strategy_id`, `ticker_id`, `timeframe_id`, `start_time`, `end_time`, `balance`, `currency`, `estatus`) VALUES ( '$user_id', '$session_strategy_id', '$ticker', '$timeFrame', '$start_time', '$end_time', '$balance', '$currency', '$status')";
            $con->query($sql);
            $last_validation_id = $con->insert_id;
            echo $last_validation_id;
        }
    } else if ($_GET['action'] == 'system_defination') {

        //$check = $_POST['insert_data'];
        updateDefLang($user_id, $lang_id, $con);
        echo getStratDef($user_id, $con);

        /*if (!empty($check)) {


			$user_id = get_current_user_id();
			$status = 'N';
			$userAlready = $con->query("SELECT * FROM session_strategy_definition WHERE user_id = $user_id");

			if (mysqli_num_rows($userAlready) > 0) {

				// INI - CGM - 10/5/2020 - distinguimos idioma ES/EN
				//$query = mysqli_query($con, "UPDATE session_strategy_definition set `estatus`= 'N' , `definition_text`=''  WHERE user_id = $user_id");

				$query = mysqli_query($con, "UPDATE session_strategy_definition set `lang_id` = '$lang_id' WHERE user_id = $user_id");

				// FIN - CGM - 10/5/2020 - distinguimos idioma ES/EN
			} else {
				$fetch_session_id = mysqli_query($con, "SELECT * FROM session_strategy WHERE user_id = $user_id");
				$sessoin_id = mysqli_fetch_assoc($fetch_session_id)['sesion_id'];

				// INI - CGM - 10/5/2020 - distinguimos idioma ES/EN
				//$query = mysqli_query($con, "INSERT INTO `session_strategy_definition`(`session_strat_def_id`, `user_id`, `definition_text`, `sesion_id`, `estatus`) VALUES (NULL, $user_id, '', $sessoin_id, '$status')");
				$query = mysqli_query($con, "INSERT INTO `session_strategy_definition`(`session_strat_def_id`, `user_id`, `definition_text`, `sesion_id`, `estatus`, `lang_id`) VALUES (NULL, $user_id, '', $sessoin_id, '$status', '$lang_id')");

				// FIN - CGM - 10/5/2020 - distinguimos idioma ES/EN
			}

			$definition_id = mysqli_query($con, "SELECT * FROM session_strategy_definition WHERE user_id = '$user_id'");
			$strategy_definition_id = mysqli_fetch_assoc($definition_id)['session_strat_def_id'];
			echo $strategy_definition_id;

		} else {

			$id = $_POST['strategy_definition_id'];
			$value = mysqli_query($con, "SELECT * FROM session_strategy_definition WHERE session_strat_def_id = $id AND estatus = 'F'");
			if ($value === FALSE) {
				printf("DB connect failed!: %s --\n", mysqli_error($con));
				echo "-----";
			}

			if (mysqli_num_rows($value) > 0) {
				$text = encodes(mysqli_fetch_assoc($value)['definition_text']);

				if (empty($text)) {

					echo "No Definition found";
				} else {

					echo $text;
				}
			}
		}
		*/
    } else if ($_GET['action'] == 'session_compiled') {
        $user_id = get_current_user_id();
        $tipo = $_POST['tipo'];
        $checkQuery = mysqli_query($con, "SELECT * FROM session_compiled WHERE user_id = $user_id");
        if ($checkQuery) {

            // Si hay registro, hacemos update
            if (mysqli_num_rows($checkQuery)) {

                // Update para las precompilaciones
                $sql = "UPDATE session_compiled SET estatus = 'N', tipo = '$tipo', fecha_hora = now(), system_down = null, system_comp = null, url_file = null, selector = -1 WHERE user_id = $user_id";
            } else {
                // Insert
                $fetch_session_id = mysqli_query($con, "SELECT sesion_id FROM session_strategy WHERE user_id = $user_id");
                $session_id = mysqli_fetch_assoc($fetch_session_id)['sesion_id'];
                $sql = "INSERT INTO `session_compiled` (`session_id`, `user_id`, `estatus`, `tipo`, `lang`) VALUES ($session_id, $user_id, 'N', '$tipo', '$lang_id')";
            }

            $query = mysqli_query($con, $sql);

            if (($tipo == 'V' || $tipo == 'D') and $query === true) {

                echo true;
            }
        }
    } else if ($_GET['action'] == 'link_status') {
        //$pay_id = $_POST['compile_id'];
        //$query = mysqli_query($con,"SELECT * FROM session_compiled WHERE session_comp_id = '$pay_id'");
        $query = mysqli_query($con, "SELECT * FROM session_compiled WHERE user_id = $user_id");
        $fetched_data = mysqli_fetch_assoc($query);
        if (mysqli_num_rows($query) > 0) {

            // echo $fetched_data['estatus'];
            if ($fetched_data['estatus'] == 'F') {
                // Send download file link
                echo encodes($fetched_data['url_file']);
            } else if ($fetched_data['estatus'] == 'E') {
                echo encodes($fetched_data['url_file'] . "complie_error");
                //echo encodes($fetched_data['result_report']);
                /*$error = mysqli_query($con, "SELECT * FROM `ERROR` WHERE ERROR_CODE = 4  AND LANG_ID = '".$lang_id."' ");
					if(mysqli_num_rows($error) > 0){
					echo encodes(mysqli_fetch_assoc($error)['ERROR_DESC'])."complie_error";
					}*/
            }
        }
    }

    // INI - Marc - 03/12/19 - Obtener por GET la última estrategia del usuario
    else if ($_GET['action'] == 'lastStrategy') {
        /**
         * Devuelve la información de la última estrategia del usuario.
         * @var string[]
         */
        $lastStrategy = getLastStrategy($con, $user_id);
        $last_strategy_name = get_strategy_name($con, $lastStrategy['strategy_id'], $lastStrategy['origin']);
        if ($lastStrategy['origin'] == "M") {
            $last_strategy_name = $last_strategy_name['name'];
        } else if ($lastStrategy['origin'] == "S") {
            $last_strategy_name = $last_strategy_name['title'];
        } else {
            $last_strategy_name = "";
        }
        $rows_open = array();
        $rows_close = array();
        fullFill_Scenario(0, $lastStrategy['open_scenario']);
        fullFill_Scenario(2, $lastStrategy['close_scenario']);

        // Enviar los datos de la última estrategia del usuario al JS
        echo json_encode([
            'id' => $lastStrategy['strategy_id'],
            'rows_open' => $rows_open,
            'rows_close' => $rows_close,
            'strategy_name' => $last_strategy_name
        ]);
    }

    // FIN - Marc - 03/12/19 - Obtener por GET la última estrategia del usuario
    // Comprueba si el usuario que realiza la petición está logeado o no

    else if ($_GET['action'] == 'user_logged') {
        if (is_user_logged_in()) {
            echo json_encode(['logged' => true]);
        } else {
            $loginURL = home_url($path = '/', $scheme = https) . 'mi-tradeasy/';
            echo json_encode(['logged' => false, 'location' => $loginURL]);
        }
    }
    // Guardar una estrategia
    else if ($_GET['action'] == 'save_strategy') {
        $user_id = get_current_user_id();
        $strat_name = $_POST['name'];
        $strat_asset = $_POST['asset'];
        $strat_timeframe = $_POST['timeframe'];
        $strat_period = $_POST['period'];
        $strat_descr = $_POST['description'];
        updateDefLang($user_id, $lang_id, $con);
        $strat_def = getStratDef($user_id, $con);
        // $strat_img = $_POST['image'];
        $open_sc = $_POST['open'];
        $close_sc = $_POST['close'];
        $createdAt = $_POST['date'];



        // Actualizar
        if (isset($_POST['sid']) and $_POST['sid'] != '0') {
            $sid = $_POST['sid'];
            $query = mysqli_query($con, "UPDATE my_strategies SET strat_definition = '$strat_def', open_scenario = '$open_sc', close_scenario = '$close_sc' WHERE strategy_id = $sid AND user_id = $user_id");

            if ($query === true) {

                $msgQuery = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME = 'SAVE_STRATEGY_TEXT8' and LANG_ID = '$lang_id'");
                $msg = encodes(mysqli_fetch_assoc($msgQuery)['TEXT']);
                echo json_encode(['success' => true, 'msg' => $msg]);
            } else {

                $msgQuery = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME = 'SAVE_STRATEGY_TEXT9' and LANG_ID = '$lang_id'");
                $msg = encodes(mysqli_fetch_assoc($msgQuery)['TEXT']);
                echo json_encode(['success' => false, 'msg' => $msg]);
            }
        }

        // Guardar
        else if (isset($strat_name) and isset($strat_descr) and isset($createdAt)) {
            $createdAt = date('Y-m-d H:i:s', strtotime($createdAt));
            $query = mysqli_query($con, "INSERT INTO my_strategies VALUES (null, $user_id, '$strat_name', '$strat_descr', '$strat_def', null, '$strat_asset', '$strat_timeframe', '$strat_period', '$open_sc', '$close_sc', now(), '$createdAt')");
            if ($query === true) {
                $sid = $con->insert_id;
                $userAlready = $con->query("SELECT * FROM session_strategy WHERE user_id = '$user_id'");
                if (mysqli_num_rows($userAlready) > 0) {
                    $sql = "UPDATE session_strategy set `strategy_id` = '$sid', `open_scenario`='$open_sc', `close_scenario`= '$close_sc', `origin` = 'M'  WHERE user_id = '$user_id' ";
                    $con->query($sql);
                } else {
                    $sql = "INSERT INTO session_strategy (`user_id`, `strategy_id`, `open_scenario`, `close_scenario`, `origin`) VALUES ( '$user_id', '$sid', '$open_scenario', '$close_scenario', M);";
                }

                $msgQuery = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME = 'SAVE_STRATEGY_TEXT6' and LANG_ID = '$lang_id'");
                $msg = encodes(mysqli_fetch_assoc($msgQuery)['TEXT']);
                echo json_encode(['success' => true, 'sid' => $sid, 'msg' => $msg]);
            } else {
                $msgQuery = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME = 'SAVE_STRATEGY_TEXT7' and LANG_ID = '$lang_id'");
                $msg = encodes(mysqli_fetch_assoc($msgQuery)['TEXT']);
                echo json_encode(['success' => false, 'msg' => $msg]);
            }
        } else {

            echo json_encode(['success' => false, 'msg' => 'Error']);
        }
    }

    // Guardar respuestas del usuario de una encuesta
    else if ($_GET['action'] == 'save_survey') {
        if (isset($_POST['answers'])) {
            $queries = array();
            $answers = array();
            // ID encuesta
            $survey_id = $_POST['sid'];
            $sql = "INSERT INTO encuesta_resp_usuario VALUES ";
            $i = 0;
            foreach ($_POST['answers'] as $answer) {
                $question_id = $answer['qid'];
                $answer_id = $answer['answer'];
                $other = $answer['other'];

                if ($i == 0) {

                    if (isset($other)) {

                        $sql .=  "(null, $survey_id, $question_id, $answer_id, $user_id, '$other')";
                    } else {

                        $sql .=  "(null, $survey_id, $question_id, $answer_id, $user_id, null)";
                    }
                } else {

                    if (isset($other)) {

                        $sql .=  ", (null, $survey_id, $question_id, $answer_id, $user_id, '$other')";
                    } else {

                        $sql .=  ", (null, $survey_id, $question_id, $answer_id, $user_id, null)";
                    }
                }
                $i++;
            }

            $query = mysqli_query($con, $sql);
            if ($query === true) {

                $query2 = mysqli_query($con, "INSERT INTO encuesta_usuario VALUES ($survey_id, $user_id, 'Y', null, now())");
                echo json_encode(['success' => true]);
            } else {

                echo json_encode(['success' => false]);
            }
        } else {

            echo json_encode(['error' => 'Error']);
        }
    } else if ($_GET['action'] == 'is_user_premium') {
        $res = $con->query("SELECT TYPE_USER FROM TE_USERS WHERE USER_ID = $user_id");
        if (mysqli_fetch_row($res)[0] == "F") {
            echo json_encode(['is_premium' => false]);
        } else {
            echo json_encode(['is_premium' => true]);
        }
    } else {
        echo "error";
    }
    ?>
<?php else : ?>

    <!--INI - Alba redireccion a premium si el user es FREE-->
    <?php
    $res = $con->query("SELECT TYPE_USER FROM TE_USERS WHERE USER_ID = " . $user_id . "");
    $type_user = mysqli_fetch_row($res)[0];
    $link = $_SERVER['SERVER_NAME'];
    // if ($type_user=='F') {
    // 	echo "<script>window.top.location.href = window.top.location.href + \"/premium/\"; </script>";
    // }
    ?>
    <!--END - Alba redireccion a premium si el user es FREE-->

    <!DOCTYPE html>
    <html lang="<?= strtolower($lang_id); ?>">

    <head>
        <input type="hidden" id="publish_key" value="<?= $p_k ?>">
        <script type="module" src="https://unpkg.com/x-frame-bypass"></script>
        <script src="https://unpkg.com/@ungap/custom-elements-builtin"></script>
        <meta charset="UTF-8">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link rel="stylesheet" href="https://unpkg.com/material-components-web@7.0.0/dist/material-components-web.min.css">
        <link rel="stylesheet" href="css/jquery-ui.css?time=<?= time(); ?>">
        <link rel="stylesheet" href="lib/bootstrap/css/bootstrap.min.css?time=<?= time(); ?>">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/css/datepicker.css" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" type="text/css" href="plugin/tooltipster/dist/css/tooltipster.bundle.min.css?time=<?= time(); ?>" />
        <link rel="stylesheet" type="text/css" href="plugin/tooltipster/dist/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-light.min.css?time=<?= time(); ?>">
        <link rel="stylesheet" type="text/css" href="css/style.css?time=<?= time(); ?>">

        <script src="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.js"></script>
        <script src="https://code.jquery.com/jquery-1.12.4.js?time=<?= time(); ?>"></script>
        <script type="text/javascript" src="js/javaScript.js?time=<?= time(); ?>"></script>
        <script src="js/jquery-ui.js?time=<?= time(); ?>"></script>
        <script type="text/javascript" src="plugin/tooltipster/dist/js/tooltipster.bundle.min.js?time=<?= time(); ?>">
        </script>
        <script src="https://kit.fontawesome.com/b17b075250.js" crossorigin="anonymous"></script>
        <script src="lib/moment.min.js?time=<?= time(); ?>"></script>
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

        <meta name="google" content="notranslate">
        <meta name="robots" content="noindex" />
        <meta name="googlebot" content="noindex" />
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <script>
            var lang = "<?= $lang_id ?>";
        </script>
    </head>

    <?php
    $el_save_obj = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME = 'STRATEGY_TEXT6' AND LANG_ID = '" . $lang_id . "'");
    $save_text = mysqli_fetch_assoc($el_save_obj)['TEXT'];
    $fetchcing_add_seq = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='STRATEGY_TEXT5' AND LANG_ID='$lang_id'");
    $add_seq_text = encodes(mysqli_fetch_assoc($fetchcing_add_seq)['TEXT']);
    ?>

    <body>
        <!-- Server Not Responding -->
        <button type="button" id="server_not_responding" data-toggle="modal" data-target="#exampleModalCenter" style="visibility: hidden;">Launch demo modal</button>
        <!-- Modal -->
        <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter_Title" aria-hidden="true" style="top: 0;">
            <div class="modal-dialog modal-dialog-centered" role="document" style="width: 375px;">
                <div class="modal-content">
                    <div class="modal-header" style="padding: 10px;">
                        <button type="button" class="close close_server_error_button" data-dismiss="modal" aria-label="Close" style="position: unset;color: #000;background: inherit;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body text-center" style="padding: 35px;">
                        <i class="fa fa-exclamation" aria-hidden="true" style="color:red;"></i>
                        <?php
                        $getMessage = mysqli_query($con, "SELECT * FROM `translations` WHERE TABLE_NAME = '(none)' AND CONCEPT_NAME = 'TIMEOUT_MESSAGE' AND REG_ID = '0' AND LANG_ID='$lang_id'");
                        ?>
                        <?= encodes(mysqli_fetch_assoc($getMessage)['TEXT']) ?>
                        <?php
                        $getTime = mysqli_query($con, "SELECT * FROM `translations` WHERE TABLE_NAME = '(none)' AND CONCEPT_NAME = 'SECONDS_TIMEOUT' AND REG_ID = '0'");
                        ?>
                        <input type="hidden" id="timeOut_seconds" value="<?= encodes(mysqli_fetch_assoc($getTime)['TEXT']) ?>" />
                    </div>
                </div>
            </div>
        </div>

        <!-- End Modal -->



        <!-- End Server Not Responding -->

        <!-- JFS - 01/07/2020 cambiar el new_user por el last_login -->

        <?php if ($last_login == '') :
            // Modal - Video introducción tradEAsy
        ?>

            <div class="modal fade" id="modalYT" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="myModalLabel" aria-hidden="true">
                <i class="material-icons" aria-label="Close" style="cursor:pointer; color: white; float: right; margin: 5px; font-size: 32px;">close</i>
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-body mb-0 p-0">
                            <div class="embed-responsive embed-responsive-16by9 z-depth-1-half">
                                <?php
                                $geturl = mysqli_query($con, "select * from config where title = 'URL_INTRO'");
                                ?>
                                <iframe class="embed-responsive-item" src="<?= encodes(mysqli_fetch_assoc($geturl)['value']) ?>" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php // Fin Modal
            ?>
            <?php // Botón para activar el modal con el video
            ?>

            <button type="button" id="show_modalYT" class="btn btn-info d-none" data-toggle="modal" data-target="#modalYT">Youtube</button>

            <script type="text/javascript">
                $(function() {
                    $('#show_modalYT').trigger('click');
                    $('#modalYT > i').click(function() {
                        var modalYT = $('div#modalYT');
                        modalYT.modal('hide');
                        modalYT.on('hidden.bs.modal', function(e) {
                            modalYT.remove();
                            $('button#show_modalYT').remove();
                        });
                    });
                });
            </script>

        <?php endif; ?>

        <?php
        // Textos para los modals
        $modal_texts = array();

        $md_texts_query = mysqli_query($con, "SELECT * FROM `translations` WHERE TABLE_NAME='(CUSTOM_MODAL)' AND LANG_ID='$lang_id'");
        while ($row = mysqli_fetch_array($md_texts_query)) {

            $key = $row['CONCEPT_NAME'];
            $modal_texts[$key] = $row['TEXT'];
        }

        ?>

        <?php // Botón para activar el modal
        ?>
        <button type="button" class="btn btn-primary d-none" data-toggle="modal" data-target="#modalLS" id="show_modalLS"></button>

        <?php // Modal recuperar estrategia
        ?>
        <div class="modal fade custom-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" id="modalLS">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $modal_texts['MODAL_DEFAULT_TITLE']; ?></h5>
                        <button type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php
                        $fetchcing_alert_text = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='STRATEGY_TEXT11' AND LANG_ID='$lang_id'");
                        $alert_text = encodes(mysqli_fetch_array($fetchcing_alert_text)['TEXT']);
                        ?>
                        <p><?= $alert_text; ?></p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn confirm-btn" id="confirmButton"><?= $modal_texts['MODAL_ACC_BUTTON']; ?></button>
                        <button type="button" class="btn" data-dismiss="modal"><?= $modal_texts['MODAL_CANCEL_BUTTON']; ?></button>
                    </div>
                </div>
            </div>
        </div>



        <?php // Botón para activar el modal
        ?>
        <button type="button" class="btn btn-primary d-none" data-toggle="modal" data-target="#customAlert" id="show_customAlert"></button>

        <?php // Modal alertas (sustituye a las alertas del navegador)
        ?>
        <div class="modal fade custom-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" id="customAlert">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" default-title="<?= $modal_texts['MODAL_DEFAULT_TITLE']; ?>"></h5>
                        <button type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p style="white-space: pre-line;"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn confirm-btn" data-dismiss="modal"><?= $modal_texts['MODAL_ACC_BUTTON']; ?></button>
                    </div>
                </div>
            </div>
        </div>

        <?php // Botón para activar el modal
        ?>
        <button type="button" class="btn btn-primary d-none" data-toggle="modal" data-target="#modalTicker" id="show_modalTicker"></button>

        <?php // Modal información activos
        ?>
        <div class="modal fade custom-modal te-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" id="modalTicker">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="outer-title">
                    <?php
                    $detailTxtQuery = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME = 'TEXT_DETAIL' and LANG_ID = '$lang_id'");
                    $detailTxt = encodes(mysqli_fetch_assoc($detailTxtQuery)['TEXT']);
                    ?>
                    <p prefix="<?= $detailTxt; ?>"><?= $detailTxt; ?></p>
                    <div class="modal-content">
                        <div class="modal-header d-none">
                            <h5 class="modal-title"></h5>
                            <button type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <?php
                            $more_info_names = array('price_point', 'nominal_lot', 'lot_min', 'lot_max', 'lot_step', 'lot_commission', 'leverage', 'gmt', 'start_date', 'end_date', 'quality', 'balance', 'spread');
                            $more_info_left = array();
                            $more_info_right = array();
                            // Query para obtener los literales para el modal 'Mas info' (en valida)
                            $ticker_more_info = mysqli_query($con, "SELECT CONCEPT_NAME, TEXT FROM translations WHERE CONCEPT_NAME LIKE 'MORE_INF_%' AND LANG_ID = '$lang_id'");
                            while ($row = mysqli_fetch_assoc($ticker_more_info)) {
                                $text = encodes($row['TEXT']);
                                if (strpos($row['CONCEPT_NAME'], 'LEFT') !== false) {
                                    array_push($more_info_left, $text);
                                } else {
                                    array_push($more_info_right, $text);
                                }
                            }
                            $index = 0;
                            ?>
                            <div class="ticker-left">
                                <?php
                                $texts_query = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME like 'TICKER_MORE_INFO_%' and LANG_ID = '$lang_id'");
                                $more_info_text1 = encodes(mysqli_fetch_assoc($texts_query)['TEXT']);
                                $more_info_text2 = encodes(mysqli_fetch_assoc($texts_query)['TEXT']);
                                ?>
                                <span><?= $more_info_text1; ?></span>
                                <ul>
                                    <?php
                                    foreach ($more_info_left as $text) {
                                        if ($index < count($more_info_names)) {
                                            echo '<li><span class="more_info_title">' . $text . '</span><span class="more_info_value" name="' . $more_info_names[$index] . '"></span></li>';
                                        } else {
                                            echo '<li><span class="more_info_title">' . $text . '</span><span class="more_info_value"></span></li>';
                                        }
                                        $index++;
                                    }
                                    ?>
                                </ul>
                            </div>
                            <div class="ticker-right">
                                <span><?= $more_info_text2; ?></span>
                                <ul>
                                    <?php
                                    $more_info_quality = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME like 'TEXT_MORE_INF_QUALITY%' and LANG_ID = '$lang_id'");
                                    $ticks_text = mysqli_fetch_assoc($more_info_quality)['TEXT'];
                                    $candles_text = mysqli_fetch_assoc($more_info_quality)['TEXT'];
                                    foreach ($more_info_right as $text) {
                                        if ($index < count($more_info_names)) {
                                            if ($more_info_names[$index] == 'quality') {
                                                echo '<li><span class="more_info_title">' . $text . '</span><p class="more_info_value" name="' . $more_info_names[$index] . '"><span id="num-ticks"></span> ' . $ticks_text . ' <span id="num-candles"></span> ' . $candles_text . '</p></li>';
                                            } else {
                                                echo '<li><span class="more_info_title">' . $text . '</span><span class="more_info_value" name="' . $more_info_names[$index] . '"></span></li>';
                                            }
                                        } else {
                                            echo '<li><span class="more_info_title">' . $text . '</span><span class="more_info_value"></span></li>';
                                        }
                                        $index++;
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn confirm-btn" data-dismiss="modal"><?= $modal_texts['MODAL_ACC_BUTTON']; ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--<div class="mdc-snackbar">
			<div class="mdc-snackbar__surface">
				<span class="material-icons" success="true">check_circle</span>
				<span class="material-icons" success="false">error</span>
				<div class="mdc-snackbar__label" role="status" aria-live="polite"></div>
				<div class="mdc-snackbar__actions">
					<button class="mdc-icon-button mdc-snackbar__dismiss material-icons" title="Dismiss">close</button>
				</div>
			</div>
		</div>-->
        <?php
        if (isset($_SESSION['myStrategy'])) {
            /**
             * Datos de la estrategia cargada.
             * @var Array
             */
            $myStrategy = $_SESSION['myStrategy'];
            unset($_SESSION['myStrategy']);
            $_SESSION['strategyname'] = $myStrategy['name'];
        } else {
            unset($_SESSION['strategyname']);
        }

        // Query textos modal Guardar estrategia
        $saveStratQuery = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME LIKE 'SAVE_STRATEGY_TEXT%' AND LANG_ID = '$lang_id' ORDER BY REG_ID");
        $saveStratText = encodes(mysqli_fetch_assoc($saveStratQuery)['TEXT']);
        // Query texto 'Guardar'
        $saveTextQuery = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME like 'STRATEGY_TEXT6' and LANG_ID = '$lang_id'");
        $saveText = encodes(mysqli_fetch_assoc($saveTextQuery)['TEXT']);

        // Query texto 'Subir Imagen'
        // $uploadImageTextQuery = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME like 'SAVE_STRATEGY_UPLOAD_IMAGE' and LANG_ID = '$lang_id'");
        // $uploadImageText = encodes(mysqli_fetch_assoc($uploadImageTextQuery)['TEXT']);

        // Tooltip upload image
        // $tooltipUploadImageTextQuery = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME like 'TOOLTIP_UPLOAD_IMAGE' and LANG_ID = '$lang_id'");
        // $tooltipUploadImageText = encodes(mysqli_fetch_assoc($tooltipUploadImageTextQuery)['TEXT']);
        /*
		INSERT INTO `autot611_builder`.`translations` (`TRANS_ID`, `TABLE_NAME`, `CONCEPT_NAME`, `REG_ID`, `TEXT`, `LANG_ID`) VALUES ('1837', '(STRATEGY_SCREEN)', 'TOOLTIP_UPLOAD_IMAGE', '0', 'Take a screenshot of the validation and save it with your strategy.', 'EN');
		INSERT INTO `autot611_builder`.`translations` (`TRANS_ID`, `TABLE_NAME`, `CONCEPT_NAME`, `REG_ID`, `TEXT`, `LANG_ID`) VALUES ('1838', '(STRATEGY_SCREEN)', 'TOOLTIP_UPLOAD_IMAGE', '0', 'Haz una captura de pantalla de la validación y guárdala con tu estrategia.', 'ES');
		 */
        ?>

        <!-- More info Popup -->
        <div class="modal fade custom-modal te-modal" tabindex="-1" role="dialog" id="modalSaveStrategy">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="outer-title">
                    <p><?= $saveStratText; ?></p>
                    <div class="modal-content">
                        <div class="modal-header d-none">
                            <h5 class="modal-title"></h5>
                            <button type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex flex-column justify-content-around">
                                <div class="d-flex align-items-baseline my-1 ">
                                    <label class="font-weight-bold m-1"><?= encodes(mysqli_fetch_assoc($saveStratQuery)['TEXT']); ?></label>
                                    <input type="text" class="form-control" name="strat_name" value="<?php if (isset($myStrategy)) echo $myStrategy['name']; ?>" placeholder="<?= encodes(mysqli_fetch_assoc($saveStratQuery)['TEXT']); ?>">
                                </div>
                                <!-- Asset selector -->
                                <div class="d-flex align-items-baseline my-1">
                                    <label for="strat-asset" class="font-weight-bold m-1 ">Activo:</label>
                                    <select name="strat-asset" id="strat-asset" class="form-control" required>
                                        <?php
                                        $assets = $con->query('SELECT display_name FROM ticker;');
                                        while ($asset = $assets->fetch_row()) {
                                        ?>
                                            <option value="<?= $asset['0'] ?>"><?= $asset['0'] ?></option>
                                        <?php
                                        }
                                        $assets->close(); // Free memory after query.
                                        ?>
                                    </select>
                                </div>
                                <!-- Timeframe selector -->
                                <div class="d-flex align-items-baseline my-1">
                                    <label for="strat-timeframe" class="font-weight-bold m-1">Timeframe:</label>
                                    <select name="strat-timeframe" id="strat-timeframe" class="form-control" required>
                                        <?php
                                        $timeframes = $con->query("SELECT TF_NAME FROM timeframes WHERE LANGUAJE_id = '$lang_id';");
                                        while ($timeframe = $timeframes->fetch_row()) {
                                        ?>
                                            <option value="<?= $timeframe['0'] ?>"><?= $timeframe['0'] ?></option>
                                        <?php
                                        }
                                        $timeframes->close(); // Free memory after query.
                                        ?>
                                    </select>
                                </div>
                                <!-- Startdate picker -->
                                <div class="d-flex flex-column my-1">
                                    <label for="save-start-date" class="font-weight-bold m-1">Fecha inicio:</label>
                                    <input type="date" name="save-start-date" id="save-start-date" value="2020-01-01">
                                </div>
                                <!-- Endate picker -->
                                <div class="d-flex flex-column my-1">
                                    <label for="save-end-date" class="font-weight-bold m-1">Fecha fin:</label>
                                    <input type="date" name="save-end-date" id="save-end-date" value="2020-01-30">
                                </div>
                                <!-- Description text -->
                                <div class="d-flex flex-column my-1">
                                    <label class="font-weight-bold"><?= encodes(mysqli_fetch_assoc($saveStratQuery)['TEXT']); ?></label>
                                    <textarea class="form-control" name="strat_descr" rows="4" placeholder="<?= encodes(mysqli_fetch_assoc($saveStratQuery)['TEXT']); ?>"><?php if (isset($myStrategy)) echo $myStrategy['description']; ?></textarea>
                                </div>
                            </div>
                            <!--<div>
                                <div class="mt-2 mb-2">
                                    <span class="d-none">
                                        <input type="file" id="strat_img" name="strat_img" accept="image/png, image/jpeg, image/jpg">
                                        <img id="strat_img_base64" src="">
                                    </span>
                                    <label for='strat_img' class='btn btn-primary mb-0' data-toggle="tooltip" data-placement="top" title="<?= $tooltipUploadImageText ?>">
                                        <span class="fas fa-upload"></span>&nbsp<span><?= $uploadImageText ?></span>
                                    </label>
                                    <br>

                                </div>
                                <div>
                                    <div class="alert alert-success" role="alert" id="succesUpladImage" style="display:none">
                                        <i class="fas fa-check"></i>&nbsp<span id="succesUploadImageMsg"></span>
                                    </div>
                                    <div class="alert alert-danger" role="alert" id="errorUpladImage" style="display:none">
                                        <i class="fas fa-exclamation-circle"></i>&nbsp<span id="errorUploadImageMsg"></span>
                                    </div>
                                </div>
                            </div>-->
                            <?php
                            if (isset($myStrategy) and $myStrategy["sid"] != '0' and $myStrategy["sid"] != '') {
                                echo '<input type="hidden" name="sid" value="' . $myStrategy["sid"] . '">';
                            }
                            ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn confirm-btn" data-action="?action=save_strategy"><i class="far fa-save"></i>&nbsp<?= $saveText; ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php // Botón para activar el modal
        ?>

        <button type="button" class="btn btn-primary d-none" data-toggle="modal" data-target="#modalConfirmSaveStrategy" id="show_confirmSaveStrategy"></button>

        <?php // Modal pregunta sobreescribir/crear estrategia
        ?>

        <div class="modal fade custom-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" id="modalConfirmSaveStrategy">

            <div class="modal-dialog" role="document">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title"><?= $modal_texts['MODAL_DEFAULT_TITLE']; ?></h5>

                        <button type="button" data-dismiss="modal" aria-label="Close">

                            <span aria-hidden="true">&times;</span>

                        </button>

                    </div>

                    <div class="modal-body">

                        <?php

                        $confirm_save_texts = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME like 'CONFIRM_SAVE_STRATEGY_TEXT%' AND LANG_ID = '$lang_id'");

                        ?>

                        <p><?= mysqli_fetch_assoc($confirm_save_texts)['TEXT']; ?></p>

                    </div>

                    <div class="modal-footer">

                        <button type="button" class="btn confirm-btn"><?= mysqli_fetch_assoc($confirm_save_texts)['TEXT']; ?></button>

                        <button type="button" data-action="?action=save_strategy" class="btn save-btn" data-dismiss="modal"><?= mysqli_fetch_assoc($confirm_save_texts)['TEXT']; ?></button>

                    </div>

                </div>

            </div>

        </div>



        <?php // Módulo encuestas



        // Query para comprobar si el primer login del usuario es de hace mas de 7 días
        // JFS - 29/09/2020 quitar la validación de los 7 días
        //$checkFirstLogin_query = mysqli_query($con, "SELECT FIRST_LOGIN FROM TE_USERS WHERE FIRST_LOGIN < (curdate() - 7) and USER_ID = $user_id");

        //if ($checkFirstLogin_query->num_rows > 0):

        // Query para comprobar si el usuario tiene encuestas disponibles

        $availableSurveys_query = mysqli_query(
            $con,

            "SELECT e.encuesta_id

FROM encuesta e

WHERE

e.activa = 'Y'

and curdate() between e.fecha_ini and e.fecha_fin

and e.encuesta_id not in ( select eu.encuesta_id from encuesta_usuario eu where eu.user_id = $user_id )"

        );



        // Si el usuario tiene una o más encuestas disponible, creamos el popup con una encuesta aleatoria de las disponibles

        if ($availableSurveys_query->num_rows > 0) :

            $ids = array();

            while ($row = mysqli_fetch_assoc($availableSurveys_query)) {

                array_push($ids, $row['encuesta_id']);

                $preg_id = 0;
            }

            // Seleccionamos un ID de encuesta aleatorio

            $randomSurveyID = $ids[mt_rand(0, count($ids) - 1)];

            $query = mysqli_query(
                $con,

                "SELECT

e.encuesta_id,

e.titulo,

e.descripcion,

p.pregunta_id,

p.texto as preg_texto,

r.respuesta_id,

r.texto as res_texto,

r.tipo_respuesta

FROM

encuesta e,

encuesta_preguntas p,

encuesta_respuesta r

WHERE

e.encuesta_id = p.encuesta_id

and p.pregunta_id = r.pregunta_id

and e.encuesta_id = $randomSurveyID"

            );



            $json_survey = array('questions' => array());

            if ($query->num_rows > 0) {

                $question = array();

                $qIndex = -1;

                while ($row = mysqli_fetch_assoc($query)) {

                    if ($row['pregunta_id'] != $preg_id) {

                        if ($preg_id == 0) {

                            $json_survey['sid'] = $row['encuesta_id'];

                            $json_survey['title'] = $row['titulo'];

                            $json_survey['description'] = $row['descripcion'];
                        }

                        $question['id'] = $row['pregunta_id'];

                        $question['text'] = $row['preg_texto'];

                        $question['answers'] = array();

                        array_push($json_survey['questions'], $question);

                        $preg_id = $row['pregunta_id'];

                        $qIndex++;
                    }

                    $answer = array('id' => $row['respuesta_id'], 'text' => $row['res_texto'], 'type' => $row['tipo_respuesta']);

                    array_push($json_survey['questions'][$qIndex]['answers'], $answer);
                }
            }



            $survey_texts = array();

            $survey_texts_query = mysqli_query($con, "SELECT CONCEPT_NAME, TEXT FROM translations WHERE (CONCEPT_NAME like 'SURVEY_TEXT%' OR CONCEPT_NAME = 'JUMPER_NEXT') and LANG_ID = '$lang_id'");
            while ($row = mysqli_fetch_assoc($survey_texts_query)) {
                if ($row['CONCEPT_NAME'] == 'JUMPER_NEXT') {
                    $survey_texts['NEXT'] = $row['TEXT'];
                } else {
                    $survey_texts[$row['CONCEPT_NAME']] = $row['TEXT'];
                }
            }
        ?>
            <div class="modal fade custom-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" id="surveyModal">

                <div class="modal-dialog modal-dialog-centered" role="document">

                    <div class="modal-content">

                        <div class="modal-header">

                            <h5 class="modal-title survey-first-step"><?= $modal_texts['MODAL_DEFAULT_TITLE']; ?></h5>

                            <h5 class="modal-title survey-second-step d-none"></h5>

                            <button type="button" class="survey-first-step" data-dismiss="modal" aria-label="Close">

                                <span aria-hidden="true">&times;</span>

                            </button>

                        </div>

                        <div class="modal-body">

                            <div class="survey-first-step">

                                <p><?php echo $survey_texts['SURVEY_TEXT1']; ?></p>

                            </div>

                            <div class="survey-second-step d-none">

                                <p class="survey-description"></p>

                                <div class="d-none" input-placeholder="<?php echo $survey_texts['SURVEY_TEXT2']; ?>">
                                    <?php echo htmlspecialchars(json_encode($json_survey), ENT_QUOTES); ?></div>

                            </div>

                            <div class="survey-third-step d-none">

                                <span><?php echo $survey_texts['SURVEY_TEXT3']; ?></span>

                            </div>

                        </div>

                        <div class="modal-footer">

                            <div class="survey-first-step">

                                <button type="button" class="btn confirm-btn"><?php echo $survey_texts['SURVEY_TEXT4']; ?></button>

                                <button type="button" class="btn" data-dismiss="modal"><?php echo $survey_texts['SURVEY_TEXT5']; ?></button>

                            </div>

                            <div class="survey-second-step d-none">

                                <button type="button" class="btn" id="nextQuestionSurvey"><?php echo $survey_texts['NEXT']; ?></button>

                                <button type="button" class="btn confirm-btn d-none" data-action="?action=save_survey"><?php echo $survey_texts['SURVEY_TEXT6']; ?></button>

                            </div>

                            <div class="survey-third-step d-none">

                                <button type="button" class="btn" data-dismiss="modal"><?= $modal_texts['MODAL_ACC_BUTTON']; ?></button>

                            </div>

                        </div>

                    </div>

                </div>

            </div>



        <?php endif; ?>

        <?php // endif;
        ?>



        <div class="box_shadow"></div>



        <!-- Mobile menu closed -->





        <!-- use js code -->

        <?php

        $helpUrl = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='STRATEGY_TEXT9_URL' AND LANG_ID='$lang_id'");

        $helpText = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='STRATEGY_TEXT8' AND LANG_ID='$lang_id'");

        $helpTranslated = encodes(mysqli_fetch_assoc($helpText)['TEXT']);

        $help_breaked = explode("?", $helpTranslated);

        $guide = substr($help_breaked[1], strrpos($help_breaked[1], ' ') + 1);

        $guide = "<a href='" . encodes(mysqli_fetch_assoc($helpUrl)['TEXT']) . "' target='_blank'>" . $guide . "</a>";

        $last_space_position = strrpos($help_breaked[1], ' ');

        $helpTranslated = substr($help_breaked[1], 0, $last_space_position);

        $help = $help_breaked[0] . "?<br>" . $helpTranslated . " " . $guide;

        ?>

        <button type="button" class="help pop" data-container="body" data-toggle="popover" data-placement="right" data-content="<?= $help ?>" data-original-title="" title="">

            <img src="images/help.png">

            <!-- &#10067  -->

        </button>

        <?php
        $user_mode = mysqli_query($con, "SELECT * FROM TE_USERS WHERE USER_ID = $user_id");
        $user_mode = mysqli_fetch_assoc($user_mode);
        $USER_NAME = $user_mode['USER_NAME'];
        $TYPE_USER = $user_mode['TYPE_USER'];

        if ($user_mode['TYPE_USER'] == 'F') {

            $welcome_text = mysqli_query($con, "SELECT Text FROM `translations` WHERE TABLE_NAME='(STRATEGY_SCREEN)' and CONCEPT_NAME='WELCOME_FREEMIUN' and LANG_ID='$lang_id'");

            $welcome_text = mysqli_fetch_assoc($welcome_text)['Text'];
        ?> <div> <?php
                    echo str_replace("@USERNAME", $USER_NAME, $welcome_text);
                    ?> </div> <?php
                            } else {
                                ?> <div>

                <?php
                                $val_days_trial = mysqli_query($con, "SELECT DATEDIFF(IFNULL(END_TRIAL,CURDATE()),CURDATE()) as `TIMETRIAL` FROM `TE_USERS` WHERE USER_ID = $user_id AND trial='Y'");
                                $days_trial = mysqli_fetch_assoc($val_days_trial)['TIMETRIAL'];
                                $val_is_premium = mysqli_query($con, "SELECT COUNT(*) as `IM_PREM` FROM `TE_USERS` WHERE USER_ID = $user_id AND TYPE_USER = 'P' AND TRIAL = 'N'");
                                $is_premium = mysqli_fetch_assoc($val_is_premium)['IM_PREM'];
                                $val_is_admin = mysqli_query($con, "SELECT COUNT(*) as `IM_PREM` FROM `TE_USERS` WHERE USER_ID = $user_id AND TYPE_USER = 'A'");
                                $is_admin = mysqli_fetch_assoc($val_is_admin)['IM_PREM'];
                                $val_is_iron = mysqli_query($con, "SELECT COUNT(*) as `IM_IRON` FROM `TE_USERS` WHERE USER_ID = $user_id AND TYPE_USER = 'P' AND TRIAL ='R'");
                                $is_iron = mysqli_fetch_assoc($val_is_iron)['IM_IRON'];

                                if ($is_admin == 0) {

                                    if ($is_premium == 0 && $is_iron == 0) {
                                        if ($days_trial > 0) {
                                            $daysleft = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='USER_STATUS_INFO1' AND LANG_ID='$lang_id'");
                                            $daysleft = encodes(mysqli_fetch_assoc($daysleft)['TEXT']);
                                            $daysleft = str_replace("@USERNAME", "<b>" . $current_user->user_login . "</b>", $daysleft);
                                            $daysleft = str_replace("&number&", "<b>" . $days_trial, $daysleft) . "</b>";



                ?><div>
                                <p>
                                    <font size="3" ; face="montserrat" ; LINE-HEIGHT:5px;> <?php echo $daysleft ?><?php

                                                                                                                } else {

                                                                                                                    $freemembership = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='USER_STATUS_INFO2' AND LANG_ID='$lang_id'");
                                                                                                                    $freemembership = encodes(mysqli_fetch_assoc($freemembership)['TEXT']);
                                                                                                                    $freemembership = str_replace("&user&", "<b>" . $current_user->user_login . "</b>", $freemembership);
                                                                                                                    $freemembership = str_replace("&memebership&", "<b>FREE</b>", $freemembership);
                                                                                                                    $freemembership = str_replace("&membership&", "<b>FREE</b>", $freemembership);

                                                                                                                    ?><p>
                                            <font size="3" ; face="montserrat" ; LINE-HEIGHT:5px;><?php echo $freemembership ?> <?php
                                                                                                                            }

                                                                                                                            //$changetopremium = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='USER_STATUS_INFO3' AND LANG_ID='$lang_id'");
                                                                                                                            //$changetopremium = encodes(mysqli_fetch_assoc($changetopremium)['TEXT']);


                                                                                                                            //echo $changetopremium
                                                                                                                                ?> </font>
                                        </p>
                            </div> <?php
                                    }

                                    if ($is_iron > 0) {

                                    ?><p>
                                <font size="3" ; face="montserrat" ; LINE-HEIGHT:5px;> Hola <b><?php echo $current_user->user_login; ?></b>,
                                    tienes cuenta <b style="color:red">PROMO IronFx</b><?php
                                                                                        ?> desbloquea <a href="https://www.ironfx.com.bm/es/register?utm_source=13080679&utm_medium=ib_link&utm_campaign=IB" target="_blank"><b> descargas aquí</b></a></font>
                            </p>
            </div> <?php
                                    }
                                } else {
                    ?><div>
            <p>
                <font size="3" ; face="montserrat" ; LINE-HEIGHT:5px;> Hola <b><?php echo $current_user->user_login; ?></b>,
                    tienes cuenta <b>ADMIN</b></font>
            </p>
        </div><?php
                                }
                            }

                ?>
</div>
<div class="container h-100 py-6 ">
    <div class="row top-row">
        <div class="left-arrow whizerd">
            <?php
            $JumperPreviousText = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='JUMPER_PREV_TEXT' AND LANG_ID='$lang_id'");
            ?>
            <p style="color: black;"><?= encodes(mysqli_fetch_assoc($JumperPreviousText)['TEXT']) ?></p>
            <img src="images/left-arrow.png">
            <p class="active-tab-left" style="color: black;"></p>
        </div>

        <div class="img-build whizerd active">
            <img src="images/build-active.png" class="img-responsive">
            <?php $JumperStrategyText = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='JUMPER_STRATEGY_TEXT' AND LANG_ID='$lang_id'"); ?>
            <p><?= encodes(mysqli_fetch_assoc($JumperStrategyText)['TEXT']) ?></p>
        </div>

        <div class="whizerd w_image">
            <div style="width: 128px; background-image: url('images/bullets.png'); height: 13px;" class="animate_bullets_left">
                &nbsp; </div>
        </div>

        <div class="img-validate whizerd">
            <img src="images/validate.png" class="img-responsive">
            <?php $JumperValidateText = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='JUMPER_VAL_TEXT' AND LANG_ID='$lang_id'");
            $validate_trans = encodes(mysqli_fetch_assoc($JumperValidateText)['TEXT']);
            if ($ATF_IS_TESTING) echo '<p> TESTING </p>';
            else echo '<!-- LIVE -->'; ?>
            <p><?= $validate_trans ?></p>
        </div>

        <div class="whizerd w_image">
            <div style="width: 128px; background-image: url('images/bullets.png'); height: 13px;" class="animate_bullets_right"> &nbsp; </div>
        </div>

        <div class="img-download whizerd">
            <img src="images/download.png" class="img-responsive">
            <?php $JumperDownloadText = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='JUMPER_DOWN_TEXT' AND LANG_ID='$lang_id'"); ?>
            <p><?= encodes(mysqli_fetch_assoc($JumperDownloadText)['TEXT']) ?></p>
        </div>

        <div class="right-arrow whizerd">
            <?php $JumperNextText = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='JUMPER_NEXT' AND LANG_ID='$lang_id'"); ?>
            <p style="color: black;"><?= encodes(mysqli_fetch_assoc($JumperNextText)['TEXT']) ?></p>
            <img src="images/right-arrow.png">
            <p class="active-tab-right" style="color: black;"><?= $validate_trans ?></p>
        </div>

    </div>
</div>

<div id="strat-btn-container" class="d-flex bd-highlight my-2">
    <div class="d-flex align-items-center">
        <h4 id="strategyName"><?= $myStrategy['name'] ?></h4>
    </div>
    <div class="p-2 bd-highlight">

        <?php

        $user_mode = mysqli_query($con, "SELECT * FROM TE_USERS WHERE USER_ID = $user_id AND TYPE_USER = 'F'");

        $user_mode = mysqli_fetch_assoc($user_mode);

        if ($user_mode['TYPE_USER'] == 'F') {

        ?>
            <div class="lock-btn">
                <button type="button" class="btn strategy-btn freemium_class"><i class="far fa-save"></i>&nbsp<?= $saveText; ?></button>
                <div name='contlock' class="lock-btn-overlay freemium_class ">
                    <div class="lock-btn-img">
                        <img src="images/candado.png" alt="Premium User" class="img-responsive lock-btn-image" style="">
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-outline-danger" id="reset_strategy" data-action="<?= $actual_link; ?>?action=reset_strategy"><i class="fas fa-trash"></i>&nbsp Nueva Estrategia</button>
        <?php
        } else {
        ?>
            <button type="button" class="btn strategy-btn" id="show_modalSaveStrategy"><i class="far fa-save"></i>&nbsp<?= $saveText; ?></button>
            <button type="button" class="btn btn-outline-danger" id="reset_strategy" data-action="<?= $actual_link; ?>?action=reset_strategy"><i class="fas fa-trash"></i>&nbsp Nueva Estrategia</button>
        <?php
        }
        ?>
    </div>
    <div class="ml-auto p-2 bd-highlight">
        <a title="Avisame" href="avisador.php" class="btn btn-secondary btn-lg btn-block " target="_blank">Avisame</a>
    </div>

</div>

<hr>







<!-- New Build Screen -->

<!-- INI Alba - 28/01/2020 - Store the descriptions of the elements in a hidden div. -->

<div id="descriptions_container" style="display:none">
    <?php
    $ElementsDescriptions = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME like 'TITLE_STRATEGY%' AND LANG_ID='$lang_id'");
    while ($element = mysqli_fetch_array($ElementsDescriptions)) {
        $Text = $element['TEXT'];
        $pText = explode(':', $Text)[1];
        $elementId = substr($Text, 0, strpos($Text, ':'));
        $test = mysqli_query($con, "SELECT value FROM `config` WHERE title='ip_test'");
        $live = mysqli_query($con, "SELECT value FROM `config` WHERE title='ip_live'");

        $ip_test = encodes(mysqli_fetch_assoc($test)['value']);
        $ip_live = encodes(mysqli_fetch_assoc($live)['value']);
    ?>
        <span id=<?= "'$elementId!desc'" ?> data-template="true" style="display: none;">
            <p class="el-desc"><?php echo $pText;  ?></p>
        </span>
    <?php
    }
    ?>

    <span id="ip_data">
        <p id="ip_live"><?php echo $ip_live;  ?></p>
        <p id="ip_test"><?php echo $ip_test;  ?></p>
    </span>
</div>

<div id="pageInfo" style="display:none">

    <?php



    $desc_title1 = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='TITLE_DESC_PAGE_TEXT1' AND LANG_ID='$lang_id'");

    $desc_text1 = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='DESC_PAGE_TEXT1' AND LANG_ID='$lang_id'");

    $desc_title2 = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='TITLE_DESC_PAGE_TEXT2' AND LANG_ID='$lang_id'");

    $desc_text2 = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='DESC_PAGE_TEXT2' AND LANG_ID='$lang_id'");

    $desc1 = encodes(mysqli_fetch_assoc($desc_title1)['TEXT']) . "!" . encodes(mysqli_fetch_assoc($desc_text1)['TEXT']);



    $desc2 = encodes(mysqli_fetch_assoc($desc_title2)['TEXT']) . "!" . encodes(mysqli_fetch_assoc($desc_text2)['TEXT']);

    ?>

    <span id=<?php echo "'builder'";  ?> data-template="true" style="display: none;">

        <p><?php echo $desc1; ?></p>

    </span>

    <span id=<?php echo "'validate'";  ?> data-template="true" style="display: none;">

        <p><?php echo $desc2; ?></p>

    </span>



</div>

<!-- FIN Alba - 28/01/2020 -->
<div class="build-tab">

    <div class="container-fluid h-100 py-6" style=" padding-left: 0 !important;   padding-bottom: 0 !important;">

        <div class="row">





            <div class="col-sm-4 left_elements_tab">

                <section class="delete-div">

                    <div>



                        <p>

                            <span><i class="fa fa-trash" aria-hidden="<"></i></span>

                            <strong>Delete</strong>

                        </p>



                    </div>

                </section>

                <section id="tabs">

                    <div class="container">

                        <h6 class="section-title">

                            <?php

                            $strategy_text_1_row = mysqli_query($con, "SELECT  `TEXT` FROM `translations` WHERE  `CONCEPT_NAME` = 'STRATEGY_TEXT1' AND LANG_ID = '$lang_id'");

                            echo encodes(mysqli_fetch_assoc($strategy_text_1_row)['TEXT']);

                            ?>

                        </h6>

                        <div class="row">

                            <div class="col-xs-12 all_elements">

                                <?php /*<nav>

	<div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">

	<?php
		$group_name_results = mysqli_query($con, "

		SELECT
		translations.TABLE_NAME,
		translations.CONCEPT_NAME,
		translations.REG_ID,
		element_group.GROUP_ID,
		element_group.GROUP_NAME,
		element_group.ORDER_ID,
		element_group.ACTIVE,
		translations.TEXT,
		translations.LANG_ID,
		translations.TRANS_ID

		FROM
		translations
		INNER JOIN element_group ON translations.REG_ID = element_group.GROUP_ID

		WHERE

		translations.TABLE_NAME = 'element_group'
		AND translations.CONCEPT_NAME = 'GROUP_NAME'
		AND translations.LANG_ID = '$lang_id' AND element_group.ACTIVE = '1'

		ORDER BY
		element_group.ORDER_ID ASC
		");

		while ($group_name_row = mysqli_fetch_array($group_name_results)) {
		$element_group_id = $group_name_row['GROUP_ID'];
		$id = $group_name_row['GROUP_NAME'].'-tab';
		$href = '#'.$group_name_row['GROUP_NAME'];
		$group_name = $group_name_row['GROUP_NAME'];
	?>

                                    <a class="nav-item nav-link <?php if($group_name_row['ORDER_ID'] == 1) echo 'active'; ?>"
                                        id="<?= $id; ?>" data-toggle="tab" href="<?= $href; ?>" role="tab"
                                        aria-controls="<?= $group_name; ?>"
                                        aria-selected="true"><span><?= encodes($group_name_row['TEXT']); ?></span></a>

                                    <?php }

	?>

                                </div>

                                </nav> */
                                ?>
                                <div class="tab-content py-3 px-3 px-sm-0" id="nav-tabContent">

                                    <?php

                                    $element_groups = mysqli_query($con, "SELECT `GROUP_NAME`, `ORDER_ID` FROM `element_group`");

                                    $tab_count = 0;

                                    while ($element_group = mysqli_fetch_array($element_groups)) {

                                        $tab_count++;

                                        $id = $element_group['GROUP_NAME'] . '-tab';

                                        $href = $element_group['GROUP_NAME'];

                                        $element_name = $element_group['GROUP_NAME'];
                                    ?>

                                        <div class="tab-pane fade <?php if ($element_group['ORDER_ID'] == 1) echo 'show active'; ?>" id="<?= $href; ?>" role="tabpanel" aria-labelledby="<?= $id; ?>" style="color: black;">

                                            <?php

                                            if ($href == "CUSTOM_ELEM") {

                                                $ul_sortable_height = "47vh";

                                                $element_descr_query = mysqli_query($con, "SELECT TEXT FROM `translations` WHERE TABLE_NAME='elements' AND CONCEPT_NAME='ELEMENT_DESCRIPTION' AND REG_ID=52 AND LANG_ID='$lang_id'");

                                                $element_descr = encodes(mysqli_fetch_assoc($element_descr_query)['TEXT']);

                                                // JFS - 09/06/2020 - multilenguaje de MORE_INFO_URL

                                                //$more_info_query = mysqli_query($con, "SELECT MORE_INFO_URL FROM elements WHERE ELEMENT_ID = 52;");

                                                $more_info_query = mysqli_query($con, "SELECT text as MORE_INFO_URL FROM translations WHERE reg_ID = 52 and table_name = 'ELEMENTS' and concept_name = 'ELEMENT_URL' and lang_id = '$lang_id';");



                                                $more_info = mysqli_fetch_array($more_info_query);

                                                $more_info_link = encodes($more_info['MORE_INFO_URL']);



                                                $more_info_TEXT_query = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME = 'STRATEGY_TEXT7' AND LANG_ID = '$lang_id';");

                                                $more_info_TEXT = encodes(mysqli_fetch_assoc($more_info_TEXT_query)['TEXT']);

                                                echo "<div id='req_customElem' class='d-flex flex-row'>

<p>$element_descr</p>

<a href='$more_info_link' target='_blank' class='btn'>$more_info_TEXT</a>

</div>";
                                            } else {

                                                $ul_sortable_height = "53vh";
                                            }

                                            ?>

                                            <div class="ui-widget ui-helper-clearfix">

                                                <ul class="gallery ui-helper-reset ui-helper-clearfix" id="sortable" style="height: <?= $ul_sortable_height; ?>; padding: 0 !important;">

                                                    <?php

                                                    $index = 0;

                                                    $user_id = get_current_user_id();

                                                    // JFS - 09/06/2020 - control multilenguaje MORE_INFO_URL

                                                    //$fetch_element_image = mysqli_query($con,"SELECT *

                                                    $fetch_element_image = mysqli_query($con, "SELECT ELEMENT_ID,

ELEMENT_GROUP_ID,

ELEMENT_NAME,

IMAGE_URL,

SOURCE_CODE,

ORDER_ID,

ACTIVE,

(SELECT text

FROM translations

WHERE reg_ID = element_id

and table_name = 'ELEMENTS'

 and concept_name = 'ELEMENT_URL'

 and lang_id = '$lang_id') as MORE_INFO_URL,

CUSTOM_ELEM

FROM elements

WHERE elements.`ELEMENT_GROUP_ID` = $element_group[1]

AND elements.ACTIVE = '1'

AND (

(elements.CUSTOM_ELEM='0') OR

(elements.CUSTOM_ELEM='1' AND elements.ELEMENT_ID IN(SELECT ELEMENT_USER.ELEMENT_ID FROM ELEMENT_USER WHERE ELEMENT_USER.USER_ID = $user_id)))

ORDER BY ORDER_ID ASC");



                                                    while ($fetch_element_data = mysqli_fetch_array($fetch_element_image)) {

                                                        $element_id = $fetch_element_data['ELEMENT_ID'];

                                                        $element_name = $fetch_element_data['ELEMENT_NAME'];

                                                        $element_img_url = $fetch_element_data['IMAGE_URL'];

                                                        $element_more_info = $fetch_element_data['MORE_INFO_URL'];

                                                        $element_name_query = mysqli_query($con, "SELECT * FROM `translations` WHERE TABLE_NAME='elements' AND CONCEPT_NAME='ELEMENT_NAME' AND REG_ID=$element_id AND LANG_ID='$lang_id'");

                                                        $element_name_tran = encodes(mysqli_fetch_assoc($element_name_query)['TEXT']);

                                                        $TRANS_ID = encodes(mysqli_fetch_assoc($element_name_query)['TRANS_ID']);

                                                        if ($element_name_tran == '') {

                                                            $element_name_tran = '&nbsp';
                                                        }

                                                        $fetching_element_description = mysqli_query($con, "SELECT * FROM `translations` WHERE TABLE_NAME='elements' AND CONCEPT_NAME='ELEMENT_DESCRIPTION' AND REG_ID=$element_id AND LANG_ID='$lang_id'");

                                                        $element_desc = mysqli_fetch_assoc($fetching_element_description);

                                                        $index++;

                                                        if ($element_id == 46 || $element_id == 47 || $element_id == 48 || $element_id == 52) {
                                                        } else {

                                                            if ($user_mode['TYPE_USER'] == 'F') {

                                                                // if use is freemium then this if condition run



                                                                //$freemium_user = mysqli_query($con, "SELECT * FROM `FREE_AVALIABLE_ITEMS` WHERE ID = '$element_id' AND TABLE_ID = 'ELEMENTS'");
                                                                $freemium_user = mysqli_query($con, "SELECT table_id as 'TABLE', id as 'ID' FROM `FREE_AVALIABLE_ITEMS` WHERE ID = '$element_id' AND TABLE_ID = 'ELEMENTS'
                                                                                                      union
                                                                                                    select table_id as 'TABLE', e.ELEMENT_ID as 'ID' from elements e, FREE_AVALIABLE_USERS t where t.USER_ID = '$user_id' and e.ELEMENT_ID = '$element_id' and table_id = 'ELEMENTS'");

                                                                $rows = mysqli_fetch_assoc($freemium_user);

                                                                $freemium_elements_name = $rows['TABLE'];

                                                                $freemium_elements_id = $rows['ID'];



                                                                if ($freemium_elements_id == $element_id) {

                                                                    // $freemium_elements_id if start

                                                    ?>

                                                                    <li class="ui-widget-content ui-corner-tr ui-state-default paramsmeters" data-tooltip-content="#sidebar_content-<?= $index; ?>">

                                                                        <p style="font-size: 12px"><?= $element_name_tran; ?></p>

                                                                        <i class="fa fa-remove delete_element" style="display: none;"></i>

                                                                        <img src="<?= $element_img_url; ?>?time=<?= time(); ?>" alt="<?= $element_name; ?>" width="96" height="90" class="img-responsive" data-elementID="<?= $element_id; ?>">

                                                                        <img src="images/seq-add.png" class="arrow_pop pop" data-toggle="tooltip" data-trigger="hover" data-placement="bottom" data-content="" alt="SEQ">



                                                                        <span class="sidebar_content-<?= $index; ?>" data-template="true" style="display: none;">



                                                                            <div class="main_head">

                                                                                <h6><?= $element_name_tran; ?></h6>

                                                                                <img src="<?= $element_img_url; ?>?time=<?= time(); ?>" alt="" class="pop_image">



                                                                            </div>

                                                                            <div class="tooltip_content_container">

                                                                                <!-- <h6><?= $element_name_tran; ?></h6> -->

                                                                                <?php



                                                                                $el_info_obj = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = 'STRATEGY_TEXT7' AND

LANG_ID = '" . $lang_id . "'
");
                                                                                ?>

                                                                                <p class="el-desc"><?= encodes($element_desc['TEXT']); ?>

                                                                                    <a href="<?= $element_more_info; ?>" target='_blank'><?= encodes(mysqli_fetch_assoc($el_info_obj)['TEXT']); ?></a>

                                                                                </p>



                                                                                <span class="close_tooltip left_side_tooltip"><i class="fa fa-close"></i></span>

                                                                                <div class="testing accordion" id="panel<?php echo $element_id; ?>">

                                                                                    <?php

                                                                                    // word that show on preemium elenment when user mode is free

                                                                                    $elements_title = mysqli_query($con, "SELECT Text FROM `translations` WHERE TABLE_NAME='(STRATEGY_SCREEN)' and CONCEPT_NAME='LOCK_DESC' and LANG_ID='$lang_id'");

                                                                                    $elements_title = mysqli_fetch_assoc($elements_title)['Text'];





                                                                                    $params_sql = "SELECT

parameters.PARAM_ID,

parameters.PARAM_NAME,

parameters.PARAM_TYPE,

parameters.DEFAULT_PARAM,

parameters.ELEMENT_ID,

parameters.ORDER_ID,

parameters.ACTIVE,

parameters.ONLYEXIT,

translations.CONCEPT_NAME,

translations.TABLE_NAME,

translations.TEXT,

translations.REG_ID,

translations.LANG_ID,

parameter_childs.PARENT_ID,

parameter_childs.PARENT_VALUE

FROM

parameters

INNER JOIN translations ON translations.REG_ID = parameters.PARAM_ID

LEFT JOIN parameter_childs ON parameters.PARAM_ID = parameter_childs.PARAM_ID

WHERE

parameters.ELEMENT_ID = $fetch_element_data[0] AND

translations.TABLE_NAME = 'PARAMETERS' AND

translations.CONCEPT_NAME = 'PARAM_NAME' AND

translations.LANG_ID = '$lang_id' AND

parameters.ACTIVE = '1'

ORDER BY ORDER_ID ASC

";







                                                                                    $fetch_parameters_value = mysqli_query($con, $params_sql);



                                                                                    while ($fetch_parameters = mysqli_fetch_array($fetch_parameters_value)) :

                                                                                        $param_id = $fetch_parameters['REG_ID'];

                                                                                        $fetching_param_description = mysqli_query($con, "SELECT * FROM translations WHERE  CONCEPT_NAME='PARAM_DESC' AND REG_ID=$param_id AND LANG_ID='$lang_id'");



                                                                                        $param_description = mysqli_fetch_assoc($fetching_param_description)["TEXT"];

                                                                                        // INI - Alba 18/11 Añadido un atributo que indica si el parametro es es solo de salida y si es asi que no se muestre

                                                                                        if ($fetch_parameters['ONLYEXIT'] == 1) {

                                                                                            $display_parameter = "display: none";
                                                                                        } else {

                                                                                            unset($display_parameter);
                                                                                        }



                                                                                        if ($fetch_parameters['PARAM_TYPE'] === 'INTEGER') { ?>



                                                                                            <div class="card" data-field-type='integer' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                                <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <h5 class="mb-0">

                                                                                                        <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                            <label><?= encodes($fetch_parameters['TEXT']) . " [value]" ?></label>

                                                                                                        </a>

                                                                                                    </h5>

                                                                                                </div>



                                                                                                <div id="param_<?php print_r($fetch_parameters['PARAM_ID']); ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                                    <div class="card-body">

                                                                                                        <p><?php echo $param_description; ?> </p>

                                                                                                        <input type="number" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm get_design <?php if (encodes($fetch_parameters['DEFAULT_PARAM']) < 0) {
                                                                                                                                                                                                                                echo "negative-per-default";
                                                                                                                                                                                                                            }; ?>" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= encodes($fetch_parameters['DEFAULT_PARAM']); ?>" disabled>

                                                                                                        <p class="error-input-report"></p>

                                                                                                    </div>

                                                                                                </div>

                                                                                            </div>



                                                                                        <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'BOOL') { ?>



                                                                                            <div class="card " data-field-type='bool' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                                <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <h5 class="mb-0">

                                                                                                        <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                            <label><?= encodes($fetch_parameters['TEXT']) . " [value]" ?></label>

                                                                                                        </a>

                                                                                                    </h5>

                                                                                                </div>



                                                                                                <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                                    <div class="card-body form-check tooltip-check round">

                                                                                                        <p><?php echo $param_description; ?>

                                                                                                            <input class="form-check-input d-none" type="checkbox" disabled id="<?= $fetch_parameters['PARAM_NAME'] ?>" name="<?= $fetch_parameters['PARAM_NAME'] ?>" <?php if ($fetch_parameters['DEFAULT_PARAM'] == '1') echo 'checked'; ?>>

                                                                                                            <label class="form-check-label"></label>

                                                                                                        </p>



                                                                                                    </div>

                                                                                                </div>

                                                                                            </div>



                                                                                        <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'STRING') { ?>



                                                                                            <div class="card" data-field-type='string' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                                <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <h5 class="mb-0">

                                                                                                        <a class="btn btn-link" rotate-icon="true" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                            <label><?= encodes($fetch_parameters['TEXT']) . " [value]" ?></label>

                                                                                                        </a>

                                                                                                    </h5>

                                                                                                </div>



                                                                                                <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                                    <div class="card-body">

                                                                                                        <p><?php echo $param_description; ?> </p>

                                                                                                        <?php if (strpos($fetch_parameters['DEFAULT_PARAM'], ':') !== false) { ?>

                                                                                                            <input type="text" name="<?= $fetch_parameters['PARAM_NAME'] ?>" maxlength="5" class="time-input form-control form-control-sm get_design" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= encodes($fetch_parameters['DEFAULT_PARAM']); ?>" disabled>



                                                                                                        <?php } else { ?>

                                                                                                            <input type="text" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm get_design" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= encodes($fetch_parameters['DEFAULT_PARAM']); ?>" disabled>

                                                                                                        <?php } ?>

                                                                                                        <p class="error-input-report"></p>

                                                                                                    </div>

                                                                                                </div>

                                                                                            </div>



                                                                                        <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'DOUBLE') { ?>



                                                                                            <div class="card" data-field-type='double' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                                <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <h5 class="mb-0">

                                                                                                        <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                            <label><?= encodes($fetch_parameters['TEXT']) . " [value]" ?></label>

                                                                                                        </a>

                                                                                                    </h5>

                                                                                                </div>



                                                                                                <div id="param_<?php print_r($fetch_parameters['PARAM_ID']); ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                                    <div class="card-body">

                                                                                                        <p><?php echo $param_description; ?> </p>

                                                                                                        <input type="text" step="any" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm get_design" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= $fetch_parameters['DEFAULT_PARAM']; ?>" disabled>

                                                                                                        <p class="error-input-report"></p>

                                                                                                    </div>

                                                                                                </div>

                                                                                            </div>







                                                                                        <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'DESPLEGABLE') {

                                                                                            $param_id = $fetch_parameters['PARAM_ID'];

                                                                                            $param_name_query = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME='PARAM_OPT' and REG_ID=$param_id and LANG_ID='$lang_id'");

                                                                                            $fetch_param_name = mysqli_fetch_array($param_name_query);

                                                                                            $options = $fetch_param_name['TEXT'];

                                                                                            $values = explode(";", $options); ?>







                                                                                            <div class="card " data-field-type='dropdown' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                                <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <h5 class="mb-0">

                                                                                                        <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                            <label><?= encodes($fetch_parameters['TEXT']) . " [value]" ?></label>

                                                                                                        </a>

                                                                                                    </h5>

                                                                                                </div>



                                                                                                <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                                    <div class="card-body">

                                                                                                        <p><?php echo $param_description; ?> </p>

                                                                                                        <select class="form-control form-control-sm get_design" disabled>

                                                                                                            <?php

                                                                                                            foreach ($values as $i => $value) {

                                                                                                                if ($fetch_parameters['DEFAULT_PARAM'] == $i) {

                                                                                                                    echo "<option value='" . encodes($value) . "' selected>" . encodes($value) . "</option>";
                                                                                                                } else {

                                                                                                                    echo "<option value='" . encodes($value) . "'>" . encodes($value) . "</option>";
                                                                                                                }
                                                                                                            }

                                                                                                            ?>

                                                                                                        </select>

                                                                                                    </div>

                                                                                                </div>

                                                                                            </div>

                                                                                        <?php } ?>

                                                                                    <?php endwhile; ?>

                                                                                </div>

                                                                                <div style="text-align: center;">

                                                                                    <button type="submit" class="btn btn-success btn-block btn-outline-success d-none close_tooltip_save"><?= $save_text; ?></button>

                                                                                </div>

                                                                            </div>

                                                                        </span>

                                                                    </li>

                                                                <?php

                                                                }

                                                                // $freemium_elements_id if end

                                                                else {

                                                                    // if freemium elements are not available for use

                                                                ?>

                                                                    <li class="ui-widget-content ui-corner-tr ui-state-default paramsmeters disabled_option" data-tooltip-content="#sidebar_content-<?= $index; ?>">

                                                                        <p style="font-size: 12px"><?= $element_name_tran; ?></p>

                                                                        <i class="fa fa-remove delete_element" style="display: none;"></i>

                                                                        <img src="<?= $element_img_url; ?>?time=<?= time(); ?>" alt="<?= $element_name; ?>" width="96" height="90" class="img-responsive" data-elementID="<?= $element_id; ?>">

                                                                        <div class="lock-overlay">

                                                                            <div class="lock-img">

                                                                                <img src="images/candado.png" name="contlock" class="img-responsive" data-elementid="<?= $element_id; ?>">
                                                                                <div class="lock-text"><?php echo $elements_title; ?></div>

                                                                            </div>

                                                                        </div>

                                                                        <img src="images/seq-add.png" class="arrow_pop pop" data-toggle="tooltip" data-trigger="hover" data-placement="bottom" data-content="" alt="SEQ">









                                                                        <span class="sidebar_content-<?= $index; ?>" data-template="true" style="display: none;">

                                                                            <div class="main_head">

                                                                                <h6><?= $element_name_tran; ?></h6>

                                                                                <img src="<?= $element_img_url; ?>?time=<?= time(); ?>" alt="" class="pop_image">



                                                                            </div>

                                                                            <div class="tooltip_content_container">

                                                                                <!-- <h6><?= $element_name_tran; ?></h6> -->

                                                                                <?php



                                                                                $el_info_obj = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = 'STRATEGY_TEXT7' AND

LANG_ID = '" . $lang_id . "'

");



                                                                                ?>

                                                                                <p class="el-desc"><?= encodes($element_desc['TEXT']); ?>

                                                                                    <a href="<?= $element_more_info; ?>" target='_blank'><?= encodes(mysqli_fetch_assoc($el_info_obj)['TEXT']); ?></a>

                                                                                </p>



                                                                                <span class="close_tooltip left_side_tooltip"><i class="fa fa-close"></i></span>

                                                                                <div class="testing accordion" id="panel<?php echo $element_id; ?>">

                                                                                    <?php



                                                                                    $params_sql = "SELECT

parameters.PARAM_ID,

parameters.PARAM_NAME,

parameters.PARAM_TYPE,

parameters.DEFAULT_PARAM,

parameters.ELEMENT_ID,

parameters.ORDER_ID,

parameters.ACTIVE,

parameters.ONLYEXIT,

translations.CONCEPT_NAME,

translations.TABLE_NAME,

translations.TEXT,

translations.REG_ID,

translations.LANG_ID,

parameter_childs.PARENT_ID,

parameter_childs.PARENT_VALUE

FROM

parameters

INNER JOIN translations ON translations.REG_ID = parameters.PARAM_ID

LEFT JOIN parameter_childs ON parameters.PARAM_ID = parameter_childs.PARAM_ID

WHERE

parameters.ELEMENT_ID = $fetch_element_data[0] AND

translations.TABLE_NAME = 'PARAMETERS' AND

translations.CONCEPT_NAME = 'PARAM_NAME' AND

translations.LANG_ID = '$lang_id' AND

parameters.ACTIVE = '1'

ORDER BY ORDER_ID ASC

";







                                                                                    $fetch_parameters_value = mysqli_query($con, $params_sql);



                                                                                    while ($fetch_parameters = mysqli_fetch_array($fetch_parameters_value)) :

                                                                                        $param_id = $fetch_parameters['REG_ID'];

                                                                                        $fetching_param_description = mysqli_query($con, "SELECT * FROM translations WHERE  CONCEPT_NAME='PARAM_DESC' AND REG_ID=$param_id AND LANG_ID='$lang_id'");



                                                                                        $param_description = mysqli_fetch_assoc($fetching_param_description)["TEXT"];

                                                                                        // INI - Alba 18/11 Añadido un atributo que indica si el parametro es es solo de salida y si es asi que no se muestre

                                                                                        if ($fetch_parameters['ONLYEXIT'] == 1) {

                                                                                            $display_parameter = "display: none";
                                                                                        } else {

                                                                                            unset($display_parameter);
                                                                                        }



                                                                                        if ($fetch_parameters['PARAM_TYPE'] === 'INTEGER') { ?>



                                                                                            <div class="card" data-field-type='integer' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                                <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <h5 class="mb-0">

                                                                                                        <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                            <label><?= encodes($fetch_parameters['TEXT']) . " [value]" ?></label>

                                                                                                        </a>

                                                                                                    </h5>

                                                                                                </div>



                                                                                                <div id="param_<?php print_r($fetch_parameters['PARAM_ID']); ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                                    <div class="card-body">

                                                                                                        <p><?php echo $param_description; ?> </p>

                                                                                                        <input type="number" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm get_design <?php if (encodes($fetch_parameters['DEFAULT_PARAM']) < 0) {
                                                                                                                                                                                                                                echo "negative-per-default";
                                                                                                                                                                                                                            }; ?>" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= encodes($fetch_parameters['DEFAULT_PARAM']); ?>" disabled>

                                                                                                        <p class="error-input-report"></p>

                                                                                                    </div>

                                                                                                </div>

                                                                                            </div>



                                                                                        <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'BOOL') { ?>



                                                                                            <div class="card " data-field-type='bool' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                                <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <h5 class="mb-0">

                                                                                                        <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                            <label><?= encodes($fetch_parameters['TEXT']) . " [value]" ?></label>

                                                                                                        </a>

                                                                                                    </h5>

                                                                                                </div>



                                                                                                <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                                    <div class="card-body form-check tooltip-check round">

                                                                                                        <p><?php echo $param_description; ?>

                                                                                                            <input class="form-check-input d-none" type="checkbox" disabled id="<?= $fetch_parameters['PARAM_NAME'] ?>" name="<?= $fetch_parameters['PARAM_NAME'] ?>" <?php if ($fetch_parameters['DEFAULT_PARAM'] == '1') echo 'checked'; ?>>

                                                                                                            <label class="form-check-label"></label>

                                                                                                        </p>



                                                                                                    </div>

                                                                                                </div>

                                                                                            </div>



                                                                                        <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'STRING') { ?>



                                                                                            <div class="card" data-field-type='string' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                                <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <h5 class="mb-0">

                                                                                                        <a class="btn btn-link" rotate-icon="true" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                            <label><?= encodes($fetch_parameters['TEXT']) . " [value]" ?></label>

                                                                                                        </a>

                                                                                                    </h5>

                                                                                                </div>



                                                                                                <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                                    <div class="card-body">

                                                                                                        <p><?php echo $param_description; ?> </p>

                                                                                                        <?php if (strpos($fetch_parameters['DEFAULT_PARAM'], ':') !== false) { ?>

                                                                                                            <input type="text" name="<?= $fetch_parameters['PARAM_NAME'] ?>" maxlength="5" class="time-input form-control form-control-sm get_design" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= encodes($fetch_parameters['DEFAULT_PARAM']); ?>" disabled>



                                                                                                        <?php } else { ?>

                                                                                                            <input type="text" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm get_design" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= encodes($fetch_parameters['DEFAULT_PARAM']); ?>" disabled>

                                                                                                        <?php } ?>

                                                                                                        <p class="error-input-report"></p>

                                                                                                    </div>

                                                                                                </div>

                                                                                            </div>



                                                                                        <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'DOUBLE') { ?>



                                                                                            <div class="card" data-field-type='double' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                                <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <h5 class="mb-0">

                                                                                                        <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                            <label><?= encodes($fetch_parameters['TEXT']) . " [value]" ?></label>

                                                                                                        </a>

                                                                                                    </h5>

                                                                                                </div>



                                                                                                <div id="param_<?php print_r($fetch_parameters['PARAM_ID']); ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                                    <div class="card-body">

                                                                                                        <p><?php echo $param_description; ?> </p>

                                                                                                        <input type="text" step="any" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm get_design" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= $fetch_parameters['DEFAULT_PARAM']; ?>" disabled>

                                                                                                        <p class="error-input-report"></p>

                                                                                                    </div>

                                                                                                </div>

                                                                                            </div>







                                                                                        <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'DESPLEGABLE') {

                                                                                            $param_id = $fetch_parameters['PARAM_ID'];

                                                                                            $param_name_query = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME='PARAM_OPT' and REG_ID=$param_id and LANG_ID='$lang_id'");

                                                                                            $fetch_param_name = mysqli_fetch_array($param_name_query);

                                                                                            $options = $fetch_param_name['TEXT'];

                                                                                            $values = explode(";", $options); ?>







                                                                                            <div class="card " data-field-type='dropdown' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                                <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <h5 class="mb-0">

                                                                                                        <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                            <label><?= encodes($fetch_parameters['TEXT']) . " [value]" ?></label>

                                                                                                        </a>

                                                                                                    </h5>

                                                                                                </div>



                                                                                                <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                                    <div class="card-body">

                                                                                                        <p><?php echo $param_description; ?> </p>

                                                                                                        <select class="form-control form-control-sm get_design" disabled>

                                                                                                            <?php

                                                                                                            foreach ($values as $i => $value) {

                                                                                                                if ($fetch_parameters['DEFAULT_PARAM'] == $i) {

                                                                                                                    echo "<option value='" . encodes($value) . "' selected>" . encodes($value) . "</option>";
                                                                                                                } else {

                                                                                                                    echo "<option value='" . encodes($value) . "'>" . encodes($value) . "</option>";
                                                                                                                }
                                                                                                            }

                                                                                                            ?>

                                                                                                        </select>

                                                                                                    </div>

                                                                                                </div>

                                                                                            </div>

                                                                                        <?php } ?>

                                                                                    <?php endwhile; ?>

                                                                                </div>

                                                                                <div style="text-align: center;">

                                                                                    <button type="submit" class="btn btn-success btn-block btn-outline-success d-none close_tooltip_save"><?= $save_text; ?></button>

                                                                                </div>

                                                                            </div>

                                                                        </span>

                                                                    </li>

                                                                <?php

                                                                }

                                                                // if freemium elements are not available for use end

                                                                // end if user mode

                                                            }

                                                            // if use is freemium then this if condition run end

                                                            else {

                                                                // if user is premium then this condition run

                                                                ?>

                                                                <li class="ui-widget-content ui-corner-tr ui-state-default paramsmeters" data-tooltip-content="#sidebar_content-<?= $index; ?>">

                                                                    <p style="font-size: 12px"><?= $element_name_tran; ?></p>

                                                                    <i class="fa fa-remove delete_element" style="display: none;"></i>

                                                                    <img src="<?= $element_img_url; ?>?time=<?= time(); ?>" alt="<?= $element_name; ?>" width="96" height="90" class="img-responsive" data-elementID="<?= $element_id; ?>">

                                                                    <img src="images/seq-add.png" class="arrow_pop pop" data-toggle="tooltip" data-trigger="hover" data-placement="bottom" data-content="" alt="SEQ">



                                                                    <span class="sidebar_content-<?= $index; ?>" data-template="true" style="display: none;">



                                                                        <div class="main_head">

                                                                            <h6><?= $element_name_tran; ?></h6>

                                                                            <img src="<?= $element_img_url; ?>?time=<?= time(); ?>" alt="" class="pop_image">



                                                                        </div>

                                                                        <div class="tooltip_content_container">

                                                                            <!-- <h6><?= $element_name_tran; ?></h6> -->

                                                                            <?php



                                                                            $el_info_obj = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = 'STRATEGY_TEXT7' AND

LANG_ID = '" . $lang_id . "'

");



                                                                            ?>

                                                                            <p class="el-desc"><?= encodes($element_desc['TEXT']); ?>

                                                                                <a href="<?= $element_more_info; ?>" target='_blank'><?= encodes(mysqli_fetch_assoc($el_info_obj)['TEXT']); ?></a>

                                                                            </p>



                                                                            <span class="close_tooltip left_side_tooltip"><i class="fa fa-close"></i></span>

                                                                            <div class="testing accordion" id="panel<?php echo $element_id; ?>">

                                                                                <?php



                                                                                $params_sql = "SELECT

parameters.PARAM_ID,

parameters.PARAM_NAME,

parameters.PARAM_TYPE,

parameters.DEFAULT_PARAM,

parameters.ELEMENT_ID,

parameters.ORDER_ID,

parameters.ACTIVE,

parameters.ONLYEXIT,

translations.CONCEPT_NAME,

translations.TABLE_NAME,

translations.TEXT,

translations.REG_ID,

translations.LANG_ID,

parameter_childs.PARENT_ID,

parameter_childs.PARENT_VALUE

FROM

parameters

INNER JOIN translations ON translations.REG_ID = parameters.PARAM_ID

LEFT JOIN parameter_childs ON parameters.PARAM_ID = parameter_childs.PARAM_ID

WHERE

parameters.ELEMENT_ID = $fetch_element_data[0] AND

translations.TABLE_NAME = 'PARAMETERS' AND

translations.CONCEPT_NAME = 'PARAM_NAME' AND

translations.LANG_ID = '$lang_id' AND

parameters.ACTIVE = '1'

ORDER BY ORDER_ID ASC

";







                                                                                $fetch_parameters_value = mysqli_query($con, $params_sql);



                                                                                while ($fetch_parameters = mysqli_fetch_array($fetch_parameters_value)) :

                                                                                    $param_id = $fetch_parameters['REG_ID'];

                                                                                    $fetching_param_description = mysqli_query($con, "SELECT * FROM translations WHERE  CONCEPT_NAME='PARAM_DESC' AND REG_ID=$param_id AND LANG_ID='$lang_id'");



                                                                                    $param_description = mysqli_fetch_assoc($fetching_param_description)["TEXT"];

                                                                                    // INI - Alba 18/11 Añadido un atributo que indica si el parametro es es solo de salida y si es asi que no se muestre

                                                                                    if ($fetch_parameters['ONLYEXIT'] == 1) {

                                                                                        $display_parameter = "display: none";
                                                                                    } else {

                                                                                        unset($display_parameter);
                                                                                    }



                                                                                    if ($fetch_parameters['PARAM_TYPE'] === 'INTEGER') { ?>



                                                                                        <div class="card" data-field-type='integer' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                            <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                <h5 class="mb-0">

                                                                                                    <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                        <label><?= encodes($fetch_parameters['TEXT']) . " [value]" ?></label>

                                                                                                    </a>

                                                                                                </h5>

                                                                                            </div>



                                                                                            <div id="param_<?php print_r($fetch_parameters['PARAM_ID']); ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                                <div class="card-body">

                                                                                                    <p><?php echo $param_description; ?> </p>

                                                                                                    <input type="number" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm get_design <?php if (encodes($fetch_parameters['DEFAULT_PARAM']) < 0) {
                                                                                                                                                                                                                            echo "negative-per-default";
                                                                                                                                                                                                                        }; ?>" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= encodes($fetch_parameters['DEFAULT_PARAM']); ?>" disabled>

                                                                                                    <p class="error-input-report"></p>

                                                                                                </div>

                                                                                            </div>

                                                                                        </div>



                                                                                    <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'BOOL') { ?>



                                                                                        <div class="card " data-field-type='bool' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                            <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                <h5 class="mb-0">

                                                                                                    <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                        <label><?= encodes($fetch_parameters['TEXT']) . " [value]" ?></label>

                                                                                                    </a>

                                                                                                </h5>

                                                                                            </div>



                                                                                            <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                                <div class="card-body form-check tooltip-check round">

                                                                                                    <p><?php echo $param_description; ?>

                                                                                                        <input class="form-check-input d-none" type="checkbox" disabled id="<?= $fetch_parameters['PARAM_NAME'] ?>" name="<?= $fetch_parameters['PARAM_NAME'] ?>" <?php if ($fetch_parameters['DEFAULT_PARAM'] == '1') echo 'checked'; ?>>

                                                                                                        <label class="form-check-label"></label>

                                                                                                    </p>



                                                                                                </div>

                                                                                            </div>

                                                                                        </div>



                                                                                    <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'STRING') { ?>



                                                                                        <div class="card" data-field-type='string' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                            <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                <h5 class="mb-0">

                                                                                                    <a class="btn btn-link" rotate-icon="true" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                        <label><?= encodes($fetch_parameters['TEXT']) . " [value]" ?></label>

                                                                                                    </a>

                                                                                                </h5>

                                                                                            </div>



                                                                                            <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                                <div class="card-body">

                                                                                                    <p><?php echo $param_description; ?> </p>

                                                                                                    <?php if (strpos($fetch_parameters['DEFAULT_PARAM'], ':') !== false) { ?>

                                                                                                        <input type="text" name="<?= $fetch_parameters['PARAM_NAME'] ?>" maxlength="5" class="time-input form-control form-control-sm get_design" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= encodes($fetch_parameters['DEFAULT_PARAM']); ?>" disabled>



                                                                                                    <?php } else { ?>

                                                                                                        <input type="text" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm get_design" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= encodes($fetch_parameters['DEFAULT_PARAM']); ?>" disabled>

                                                                                                    <?php } ?>

                                                                                                    <p class="error-input-report"></p>

                                                                                                </div>

                                                                                            </div>

                                                                                        </div>



                                                                                    <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'DOUBLE') { ?>



                                                                                        <div class="card" data-field-type='double' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                            <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                <h5 class="mb-0">

                                                                                                    <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                        <label><?= encodes($fetch_parameters['TEXT']) . " [value]" ?></label>

                                                                                                    </a>

                                                                                                </h5>

                                                                                            </div>



                                                                                            <div id="param_<?php print_r($fetch_parameters['PARAM_ID']); ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                                <div class="card-body">

                                                                                                    <p><?php echo $param_description; ?> </p>

                                                                                                    <input type="text" step="any" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm get_design" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= $fetch_parameters['DEFAULT_PARAM']; ?>" disabled>

                                                                                                    <p class="error-input-report"></p>

                                                                                                </div>

                                                                                            </div>

                                                                                        </div>







                                                                                    <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'DESPLEGABLE') {

                                                                                        $param_id = $fetch_parameters['PARAM_ID'];

                                                                                        $param_name_query = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME='PARAM_OPT' and REG_ID=$param_id and LANG_ID='$lang_id'");

                                                                                        $fetch_param_name = mysqli_fetch_array($param_name_query);

                                                                                        $options = $fetch_param_name['TEXT'];

                                                                                        $values = explode(";", $options); ?>







                                                                                        <div class="card " data-field-type='dropdown' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                            <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                <h5 class="mb-0">

                                                                                                    <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                        <label><?= encodes($fetch_parameters['TEXT']) . " [value]" ?></label>

                                                                                                    </a>

                                                                                                </h5>

                                                                                            </div>



                                                                                            <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                                <div class="card-body">

                                                                                                    <p><?php echo $param_description; ?> </p>

                                                                                                    <select class="form-control form-control-sm get_design" disabled>

                                                                                                        <?php

                                                                                                        foreach ($values as $i => $value) {

                                                                                                            if ($fetch_parameters['DEFAULT_PARAM'] == $i) {

                                                                                                                echo "<option value='" . encodes($value) . "' selected>" . encodes($value) . "</option>";
                                                                                                            } else {

                                                                                                                echo "<option value='" . encodes($value) . "'>" . encodes($value) . "</option>";
                                                                                                            }
                                                                                                        }

                                                                                                        ?>

                                                                                                    </select>

                                                                                                </div>

                                                                                            </div>

                                                                                        </div>

                                                                                    <?php } ?>

                                                                                <?php endwhile; ?>

                                                                            </div>

                                                                            <div style="text-align: center;">

                                                                                <button type="submit" class="btn btn-success btn-block btn-outline-success d-none close_tooltip_save"><?= $save_text; ?></button>

                                                                            </div>

                                                                        </div>

                                                                    </span>

                                                                </li>

                                                    <?php

                                                            }

                                                            // if user is premium then this condition run end



                                                        }
                                                    }

                                                    // elements while loop end

                                                    ?>

                                                    <?php if ($tab_count == 1) :

                                                        // JFS - 09/06/2020 - control multilenguaje MORE_INFO_URL
                                                    ?>

                                                        <?php $fetch_tabs_omc = mysqli_query($con, "SELECT ELEMENT_ID,

ELEMENT_GROUP_ID,

ELEMENT_NAME,

IMAGE_URL,

SOURCE_CODE,

ORDER_ID,

ACTIVE,

(SELECT text

FROM translations

WHERE reg_ID = element_id

and table_name = 'ELEMENTS'

 and concept_name = 'ELEMENT_URL'

 and lang_id = '$lang_id') as MORE_INFO_URL,

CUSTOM_ELEM

FROM `elements` WHERE ELEMENT_GROUP_ID = 0");





                                                        $indexx = 0;

                                                        while ($fetching_omc = mysqli_fetch_array($fetch_tabs_omc)) {

                                                            $indexx++;



                                                            $id = $fetching_omc['ELEMENT_ID'];

                                                            $fetch_parameters_value = mysqli_query($con, "SELECT * FROM parameters WHERE ELEMENT_ID = $id");



                                                            $new_con = '';



                                                            $el_description = '';



                                                            if ($fetching_omc['ELEMENT_NAME'] == 'OPEN') {

                                                                $strategy = 'STRATEGY_OPEN';
                                                            } elseif ($fetching_omc['ELEMENT_NAME'] == 'CLOSE') {

                                                                $strategy = 'STRATEGY_CLOSE';
                                                            } elseif ($fetching_omc['ELEMENT_NAME'] == 'MODIFY') {

                                                                $strategy = 'STRATEGY_MODIFY';
                                                            } elseif ($fetching_omc['ELEMENT_NAME'] == 'SEQ') {

                                                                $strategy = 'ELEMENT_NAME';

                                                                $new_con = "REG_ID = '$id' AND";
                                                            }



                                                            $fetching_element_description = mysqli_query($con, "SELECT * FROM `translations` WHERE TABLE_NAME='ELEMENTS' AND CONCEPT_NAME='ELEMENT_DESCRIPTION' AND REG_ID=$id AND LANG_ID='$lang_id'");

                                                            $el_description = encodes(mysqli_fetch_assoc($fetching_element_description)['TEXT']);



                                                            $el_name_obj = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = '" . $strategy . "' AND

" . $new_con . "

LANG_ID = '" . $lang_id . "'

");

                                                            $el_name_ac = encodes(mysqli_fetch_assoc($el_name_obj)['TEXT']);



                                                            $el_name = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = 'ELEMENT_NAME' AND TABLE_NAME='ELEMENTS'AND REG_ID=$id AND LANG_ID='$lang_id'");



                                                            $el_name_SEQ = encodes(mysqli_fetch_assoc($el_name)['TEXT']);







                                                        ?>





                                                            <input type="hidden" name="unique" value="<?= $el_name_SEQ ?>">



                                                            <li class="ui-widget-content ui-corner-tr ui-state-default paramsmeters d-sort door_image_li shadow" data-tooltip-content="#tab_one_content-<?= $indexx; ?>" data-element-append="<?= $id; ?>" data-title="<?= $fetching_omc['ELEMENT_NAME']; ?>" style="display: none;">



                                                                <p style="font-size: 12px" class="v-h"><?= $el_name_ac; ?></p>



                                                                <?php if ($fetching_omc['ELEMENT_NAME'] == 'SEQ') :

                                                                    $img_src = 'images/seq-add.png';

                                                                ?>

                                                                    <i class="fa fa-remove"></i>



                                                                    <img src="images/seq-add.png" alt="<?= $fetching_omc['ELEMENT_NAME']; ?>" width="96" class="img-responsive">

                                                                    <strong><?= $add_seq_text; ?></strong>

                                                                <?php else :

                                                                    $img_src = $fetching_omc["IMAGE_URL"];

                                                                ?>

                                                                    <img src="<?= $fetching_omc['IMAGE_URL']; ?>?time=<?= time(); ?>" alt="<?= $fetching_omc['ELEMENT_NAME']; ?>" width="96" height="90" class="img-responsive" data-elementID="<?= $id; ?>">

                                                                <?php endif ?>



                                                                <!-- New tooltip design -->





                                                                <span class="tab_one_content-<?= $indexx; ?>" data-template="true" style="display: none;">



                                                                    <?php

                                                                    $open_modify_close_name = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = 'ELEMENT_NAME' AND REG_ID = $id AND LANG_ID = '" . $lang_id . "'

");

                                                                    ?>



                                                                    <div class="main_head">

                                                                        <h6><?= encodes(mysqli_fetch_assoc($open_modify_close_name)['TEXT']); ?>
                                                                        </h6>

                                                                        <img src="<?= $img_src; ?>?time=<?= time(); ?>" alt="" class="pop_image">

                                                                    </div>



                                                                    <!-- <div class="design_1"> -->

                                                                    <div class="tooltip_content_container">

                                                                        <?php



                                                                        $el_info_obj = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = 'STRATEGY_TEXT7' AND

LANG_ID = '" . $lang_id . "'

");



                                                                        $open_modify_close_name = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = 'ELEMENT_NAME' AND REG_ID = $id AND LANG_ID = '" . $lang_id . "'

");



                                                                        ?>



                                                                        <p class="el-desc design_p"> <?= $el_description; ?><a href="<?= $fetching_omc['MORE_INFO_URL']; ?>" target='_blank'>
                                                                                <?= encodes(mysqli_fetch_assoc($el_info_obj)['TEXT']); ?>
                                                                            </a></p>

                                                                        <!-- <hr class="element_line_break"> -->

                                                                        <span class="close_tooltip"><i class="fa fa-close"></i></span>



                                                                        <div class="testing accordion" id="panel<?php echo $element_id; ?>">





                                                                            <?php

                                                                            $params_sql = "SELECT parameters.*,
                                                                                                parameter_childs.PARENT_ID,
                                                                                                parameter_childs.PARENT_VALUE
                                                                                           FROM parameters
                                                                                           LEFT JOIN parameter_childs ON parameters.PARAM_ID = parameter_childs.PARAM_ID
                                                                                          WHERE ELEMENT_ID = $id AND ACTIVE = '1' ORDER BY ORDER_ID ASC ";



                                                                            $fetch_parameters_value = mysqli_query($con, $params_sql);



                                                                            while ($fetch_parameters = mysqli_fetch_array($fetch_parameters_value)) :

                                                                                $param_id = $fetch_parameters['PARAM_ID'];

                                                                                $fetching_param_description = mysqli_query($con, "SELECT * FROM translations WHERE  CONCEPT_NAME='PARAM_DESC' AND REG_ID=$param_id AND LANG_ID='$lang_id'");



                                                                                $param_description = mysqli_fetch_assoc($fetching_param_description)["TEXT"];



                                                                                $fetching_param_label = mysqli_query($con, "SELECT * FROM `translations` WHERE TABLE_NAME='PARAMETERS' AND CONCEPT_NAME='PARAM_NAME' AND REG_ID={$fetch_parameters['PARAM_ID']} AND LANG_ID='$lang_id'");

                                                                                $param_label = encodes(mysqli_fetch_assoc($fetching_param_label)['TEXT']);

                                                                                //INPUT CLOSE

                                                                                if ($fetch_parameters['PARAM_TYPE'] === 'INTEGER') { ?>



                                                                                    <div class="card" data-field-type='integer' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                        <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                            <h5 class="mb-0">

                                                                                                <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <label><?= $param_label . " [value]" ?></label>

                                                                                                </a>

                                                                                            </h5>

                                                                                        </div>



                                                                                        <div id="param_<?php print_r($fetch_parameters['PARAM_ID']); ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                            <div class="card-body">

                                                                                                <p><?php echo $param_description; ?> </p>

                                                                                                <input type="number" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm element_form" placeholder="<?= $param_label; ?>" value="<?= encodes($fetch_parameters['DEFAULT_PARAM']); ?>" disabled>

                                                                                                <p class="error-input-report"></p>

                                                                                            </div>

                                                                                        </div>

                                                                                    </div>



                                                                                <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'BOOL') { ?>





                                                                                    <div class="card " data-field-type='bool' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                        <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                            <h5 class="mb-0">

                                                                                                <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <label><?= $param_label . " [value]" ?></label>

                                                                                                </a>

                                                                                            </h5>

                                                                                        </div>



                                                                                        <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                            <div class="card-body form-check tooltip-check round">

                                                                                                <p><?php echo $param_description; ?>

                                                                                                    <input class="form-check-input d-none" type="checkbox" disabled id="<?= $fetch_parameters['PARAM_NAME'] ?>" name="<?= $fetch_parameters['PARAM_NAME'] ?>" <?php if ($fetch_parameters['DEFAULT_PARAM'] == '1') echo 'checked'; ?>>

                                                                                                    <label class="form-check-label"></label>

                                                                                                </p>



                                                                                            </div>

                                                                                        </div>

                                                                                    </div>







                                                                                <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'DOUBLE') { ?>



                                                                                    <div class="card" data-field-type='double' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                        <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                            <h5 class="mb-0">

                                                                                                <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <label><?= $param_label . " [value]" ?></label>

                                                                                                </a>

                                                                                            </h5>

                                                                                        </div>



                                                                                        <div id="param_<?php print_r($fetch_parameters['PARAM_ID']); ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                            <div class="card-body">

                                                                                                <p><?php echo $param_description; ?> </p>

                                                                                                <input type="text" step="any" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm element_form" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= $fetch_parameters['DEFAULT_PARAM']; ?>" disabled>

                                                                                                <p class="error-input-report"></p>

                                                                                            </div>

                                                                                        </div>

                                                                                    </div>





                                                                                <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'STRING') { ?>





                                                                                    <div class="card" data-field-type='string' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                        <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                            <h5 class="mb-0">

                                                                                                <a class="btn btn-link" rotate-icon="true" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <label><?= $param_label . " [value]" ?></label>

                                                                                                </a>

                                                                                            </h5>

                                                                                        </div>



                                                                                        <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                            <div class="card-body">

                                                                                                <p><?php echo $param_description; ?> </p>

                                                                                                <input type="text" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm element_form" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= $fetch_parameters['DEFAULT_PARAM']; ?>" disabled>

                                                                                                <p class="error-input-report"></p>

                                                                                            </div>

                                                                                        </div>

                                                                                    </div>



                                                                                <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'DESPLEGABLE') {

                                                                                    $param_id = $fetch_parameters['PARAM_ID'];

                                                                                    $param_name_query = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME='PARAM_OPT' and REG_ID=$param_id and LANG_ID='$lang_id'");

                                                                                    $fetch_param_name = mysqli_fetch_array($param_name_query);

                                                                                    $options = $fetch_param_name['TEXT'];

                                                                                    $values = explode(";", $options); ?>



                                                                                    <div class="card " data-field-type='dropdown' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                        <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                            <h5 class="mb-0">

                                                                                                <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <label><?= $param_label . " [value]" ?></label>

                                                                                                </a>

                                                                                            </h5>

                                                                                        </div>



                                                                                        <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                            <div class="card-body">

                                                                                                <p><?php echo $param_description; ?> </p>

                                                                                                <select class="form-control form-control-sm get_design" disabled>

                                                                                                    <?php

                                                                                                    foreach ($values as $i => $value) {

                                                                                                        if ($fetch_parameters['DEFAULT_PARAM'] == $i) {

                                                                                                            echo "<option value='" . encodes($value) . "' selected>" . encodes($value) . "</option>";
                                                                                                        } else {

                                                                                                            echo "<option value='" . encodes($value) . "'>" . encodes($value) . "</option>";
                                                                                                        }
                                                                                                    }

                                                                                                    ?>

                                                                                                </select>

                                                                                            </div>

                                                                                        </div>

                                                                                    </div>

                                                                                <?php } ?>



                                                                            <?php endwhile; ?>

                                                                        </div>

                                                                        <div style="text-align: center;">



                                                                            <button type="submit" class="btn btn-success btn-block btn-outline-success d-none close_tooltip_save "><?= $save_text; ?></button>

                                                                        </div>



                                                                    </div>

                                                                </span>

                                                            </li>

                                                        <?php

                                                        }



                                                        // INI - Marc - 12/11/19 - añade Señal Inversa en reglas de salida por defecto

                                                        // JFS - 09/06/2020 - control multilenguaje MORE_INFO_URL

                                                        //$fetch_default_elem = mysqli_query($con,"SELECT * FROM `elements` WHERE ELEMENT_ID=49");

                                                        $fetch_default_elem = mysqli_query($con, "SELECT ELEMENT_ID,

ELEMENT_GROUP_ID,

ELEMENT_NAME,

IMAGE_URL,

SOURCE_CODE,

ORDER_ID,

ACTIVE,

(SELECT text

FROM translations

WHERE reg_ID = element_id

and table_name = 'ELEMENTS'

and concept_name = 'ELEMENT_URL'

and lang_id = '$lang_id') as MORE_INFO_URL,

CUSTOM_ELEM

FROM `elements` WHERE ELEMENT_ID=49");









                                                        while ($default_elem = mysqli_fetch_array($fetch_default_elem)) {

                                                            $id = $default_elem['ELEMENT_ID'];
                                                            // echo $default_elem['ELEMENT_ID'];
                                                            $fetch_parameters_value_element = mysqli_query($con, "SELECT * FROM parameters WHERE ELEMENT_ID = $id");



                                                            $defel_description = '';

                                                            $fetching_def_element_description = mysqli_query($con, "SELECT * FROM `translations` WHERE TABLE_NAME='ELEMENTS' AND CONCEPT_NAME='ELEMENT_DESCRIPTION' AND REG_ID=$id AND LANG_ID='$lang_id'");

                                                            $defel_description = encodes(mysqli_fetch_assoc($fetching_def_element_description)['TEXT']);



                                                            $defel_name_obj = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = 'ELEMENT_NAME' AND

LANG_ID = '" . $lang_id . "'

");

                                                            $defel_name_ac = encodes(mysqli_fetch_assoc($defel_name_obj)['TEXT']);



                                                            $defel_name = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME = 'ELEMENT_NAME' AND TABLE_NAME='ELEMENTS'AND REG_ID=$id AND LANG_ID='$lang_id'");

                                                            $defel_name_SEQ = encodes(mysqli_fetch_assoc($defel_name)['TEXT']);

                                                            $TRANS_ID = encodes(mysqli_fetch_assoc($defel_name)['TRANS_ID']);

                                                        ?>
                                                            <li class="ui-widget-content ui-corner-tr ui-state-default paramsmeters ui-draggable ui-draggable-handle shadow" data-tooltip-content="#gallery_new<?= $id; ?>tooltip-content10" element_tab_index=11 data-element-append="<?= $id; ?>" style="display: none;">

                                                                <p style="font-size: 12px"><?= $defel_name_SEQ ?></p>
                                                                <i class="fa fa-remove delete_element" style="display: none;"></i>
                                                                <?php
                                                                $img_src = $default_elem["IMAGE_URL"];
                                                                ?>
                                                                <img src="<?= $default_elem['IMAGE_URL']; ?>?time=<?= time(); ?>" alt="<?= $default_elem['ELEMENT_NAME']; ?>" width="96" height="90" class="img-responsive" data-elementID="<?= $id; ?>">
                                                                <!-- New tooltip design -->
                                                                <span class="gallery_new<?= $id; ?>tooltip-content10" data-template="true" style="display: none;">
                                                                    <?php
                                                                    $open_modify_close_name = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME = 'ELEMENT_NAME' AND REG_ID = $id AND LANG_ID = '" . $lang_id . "'");
                                                                    ?>
                                                                    <div class="main_head">

                                                                        <h6><?= encodes(mysqli_fetch_assoc($open_modify_close_name)['TEXT']); ?>
                                                                        </h6>

                                                                        <img src="<?= $img_src; ?>?time=<?= time(); ?>" alt="" class="pop_image">

                                                                    </div>



                                                                    <div class="tooltip_content_container">

                                                                        <?php



                                                                        $el_info_obj = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = 'STRATEGY_TEXT7' AND

LANG_ID = '" . $lang_id . "'

");



                                                                        $open_modify_close_name = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = 'ELEMENT_NAME' AND REG_ID = $id AND LANG_ID = '" . $lang_id . "'

");



                                                                        ?>



                                                                        <p class="el-desc"> <?= $defel_description; ?><a href="<?= $default_elem['MORE_INFO_URL']; ?>" target='_blank'>
                                                                                <?= encodes(mysqli_fetch_assoc($el_info_obj)['TEXT']); ?>
                                                                            </a></p>

                                                                        <span class="close_tooltip left_side_tooltip"><i class="fa fa-close"></i></span>



                                                                        <div class="testing accordion" id="panel<?php echo $element_id; ?>">





                                                                            <?php

                                                                            $params_sql = "SELECT

*

FROM

parameters

WHERE

ELEMENT_ID = $id AND ACTIVE = '1' ORDER BY ORDER_ID ASC ";



                                                                            $fetch_parameters_value_element = mysqli_query($con, $params_sql);



                                                                            while ($fetch_parameters = mysqli_fetch_array($fetch_parameters_value_element)) :





                                                                                $param_id = $fetch_parameters['REG_ID'];

                                                                                $fetching_param_description = mysqli_query($con, "SELECT * FROM translations WHERE  CONCEPT_NAME='PARAM_DESC' AND REG_ID=$param_id AND LANG_ID='$lang_id'");



                                                                                $param_description = mysqli_fetch_assoc($fetching_param_description)["TEXT"];



                                                                                $fetching_param_label = mysqli_query($con, "SELECT * FROM `translations` WHERE TABLE_NAME='PARAMETERS' AND CONCEPT_NAME='PARAM_NAME' AND REG_ID={$fetch_parameters['PARAM_ID']} AND LANG_ID='$lang_id'");

                                                                                $param_label = encodes(mysqli_fetch_assoc($fetching_param_label)['TEXT']);

                                                                                //INPUT CLOSE

                                                                                if ($fetch_parameters['PARAM_TYPE'] === 'INTEGER') { ?>



                                                                                    <div class="card" data-field-type='integer' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                        <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                            <h5 class="mb-0">

                                                                                                <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <label><?= $param_label . " [value]" ?></label>

                                                                                                </a>

                                                                                            </h5>

                                                                                        </div>



                                                                                        <div id="param_<?php print_r($fetch_parameters['PARAM_ID']); ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                            <div class="card-body">

                                                                                                <p><?php echo $param_description; ?> </p>

                                                                                                <input type="number" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm element_form" placeholder="<?= $param_label; ?>" value="<?= encodes($fetch_parameters['DEFAULT_PARAM']); ?>" disabled>

                                                                                                <p class="error-input-report"></p>

                                                                                            </div>

                                                                                        </div>

                                                                                    </div>



                                                                                <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'BOOL') { ?>





                                                                                    <div class="card " data-field-type='bool' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                        <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                            <h5 class="mb-0">

                                                                                                <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <label><?= $param_label . " [value]" ?></label>

                                                                                                </a>

                                                                                            </h5>

                                                                                        </div>



                                                                                        <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                            <div class="card-body form-check tooltip-check round">

                                                                                                <p><?php echo $param_description; ?>

                                                                                                    <input class="form-check-input d-none" type="checkbox" disabled id="<?= $fetch_parameters['PARAM_NAME'] ?>" name="<?= $fetch_parameters['PARAM_NAME'] ?>" <?php if ($fetch_parameters['DEFAULT_PARAM'] == '1') echo 'checked'; ?>>

                                                                                                    <label class="form-check-label"></label>

                                                                                                </p>



                                                                                            </div>

                                                                                        </div>

                                                                                    </div>







                                                                                <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'DOUBLE') { ?>



                                                                                    <div class="card" data-field-type='double' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                        <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                            <h5 class="mb-0">

                                                                                                <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <label><?= $param_label . " [value]" ?></label>

                                                                                                </a>

                                                                                            </h5>

                                                                                        </div>



                                                                                        <div id="param_<?php print_r($fetch_parameters['PARAM_ID']); ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                            <div class="card-body">

                                                                                                <p><?php echo $param_description; ?> </p>

                                                                                                <input type="text" step="any" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm element_form" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= $fetch_parameters['DEFAULT_PARAM']; ?>" disabled>

                                                                                                <p class="error-input-report"></p>

                                                                                            </div>

                                                                                        </div>

                                                                                    </div>





                                                                                <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'STRING') { ?>





                                                                                    <div class="card" data-field-type='string' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                        <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                            <h5 class="mb-0">

                                                                                                <a class="btn btn-link" rotate-icon="true" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <label><?= $param_label . " [value]" ?></label>

                                                                                                </a>

                                                                                            </h5>

                                                                                        </div>



                                                                                        <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                            <div class="card-body">

                                                                                                <p><?php echo $param_description; ?> </p>

                                                                                                <input type="text" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm element_form" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= $fetch_parameters['DEFAULT_PARAM']; ?>" disabled>

                                                                                                <p class="error-input-report"></p>

                                                                                            </div>

                                                                                        </div>

                                                                                    </div>



                                                                                <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'DESPLEGABLE') {

                                                                                    $param_id = $fetch_parameters['PARAM_ID'];

                                                                                    $param_name_query = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME='PARAM_OPT' and REG_ID=$param_id and LANG_ID='$lang_id'");

                                                                                    $fetch_param_name = mysqli_fetch_array($param_name_query);

                                                                                    $options = $fetch_param_name['TEXT'];

                                                                                    $values = explode(";", $options); ?>



                                                                                    <div class="card " data-field-type='dropdown' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                        <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                            <h5 class="mb-0">

                                                                                                <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <label><?= $param_label . " [value]" ?></label>

                                                                                                </a>

                                                                                            </h5>

                                                                                        </div>



                                                                                        <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                            <div class="card-body">

                                                                                                <p><?php echo $param_description; ?> </p>

                                                                                                <select class="form-control form-control-sm get_design" disabled>

                                                                                                    <?php

                                                                                                    foreach ($values as $i => $value) {

                                                                                                        if ($fetch_parameters['DEFAULT_PARAM'] == $i) {

                                                                                                            echo "<option value='" . encodes($value) . "' selected>" . encodes($value) . "</option>";
                                                                                                        } else {

                                                                                                            echo "<option value='" . encodes($value) . "'>" . encodes($value) . "</option>";
                                                                                                        }
                                                                                                    }

                                                                                                    ?>

                                                                                                </select>

                                                                                            </div>

                                                                                        </div>

                                                                                    </div>

                                                                                <?php } ?>



                                                                            <?php endwhile; ?>

                                                                        </div>

                                                                        <div style="text-align: center;">



                                                                            <button type="submit" class="btn btn-success btn-block btn-outline-success d-none close_tooltip_save "><?= $save_text; ?></button>

                                                                        </div>



                                                                    </div>

                                                                </span>

                                                            </li>



                                                        <?php

                                                        }



                                                        // FIN - Marc - 12/11/19 - añade Señal Inversa en reglas de salida por defecto



                                                        // New element configuration



                                                        // JFS - 09/06/2020 - control multilenguaje MORE_INFO_URL

                                                        //$fetch_configuration_omc = mysqli_query($con,"SELECT * FROM `elements` WHERE ELEMENT_GROUP_ID = '4' AND  ELEMENT_ID NOT IN (4, 27, 43, 44, 45)");

                                                        $fetch_configuration_omc = mysqli_query($con, "SELECT ELEMENT_ID,

ELEMENT_GROUP_ID,

ELEMENT_NAME,

IMAGE_URL,

SOURCE_CODE,

ORDER_ID,

ACTIVE,

(SELECT text

FROM translations

WHERE reg_ID = element_id

and table_name = 'ELEMENTS'

and concept_name = 'ELEMENT_URL'

and lang_id = '$lang_id') as MORE_INFO_URL,

CUSTOM_ELEM

FROM `elements` WHERE ELEMENT_GROUP_ID = '4' AND  ELEMENT_ID NOT IN (4, 27, 43, 44, 45)");







                                                        $index_id = 7;

                                                        while ($configuration_omc = mysqli_fetch_array($fetch_configuration_omc)) {

                                                            $index_id++;



                                                            $id = $configuration_omc['ELEMENT_ID'];

                                                            $fetch_parameters_value = mysqli_query($con, "SELECT * FROM parameters WHERE ELEMENT_ID = $id");







                                                            $el_description = '';





                                                            $fetching_element_description = mysqli_query($con, "SELECT * FROM `translations` WHERE TABLE_NAME='ELEMENTS' AND CONCEPT_NAME='ELEMENT_DESCRIPTION' AND REG_ID=$id AND LANG_ID='$lang_id'");

                                                            $el_description = encodes(mysqli_fetch_assoc($fetching_element_description)['TEXT']);



                                                            $el_name_obj = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = 'ELEMENT_NAME' AND

LANG_ID = '" . $lang_id . "'

");

                                                            $el_name_ac = encodes(mysqli_fetch_assoc($el_name_obj)['TEXT']);



                                                            $el_name = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = 'ELEMENT_NAME' AND TABLE_NAME='ELEMENTS'AND REG_ID=$id AND LANG_ID='$lang_id'");



                                                            $el_name_SEQ = encodes(mysqli_fetch_assoc($el_name)['TEXT']);







                                                        ?>





                                                            <input type="hidden" name="unique" value="<?= $el_name_SEQ ?>">



                                                            <li class="ui-widget-content ui-corner-tr ui-state-default paramsmeters d-sort shadow configuration" element_tab_index='<?= $index_id; ?>' data-tooltip-content="#tab_one_content-<?= $indexx; ?>" data-element-append_conf="<?= $id; ?>" data-title="<?= $fetching_omc['ELEMENT_NAME']; ?>" style="display: none;">



                                                                <p style="font-size: 12px" class="v-h"><?= $el_name_ac; ?></p>



                                                                <?php

                                                                $img_src = $configuration_omc["IMAGE_URL"];

                                                                ?>

                                                                <img src="<?= $configuration_omc['IMAGE_URL']; ?>?time=<?= time(); ?>" alt="<?= $fetching_omc['ELEMENT_NAME']; ?>" width="96" height="90" class="img-responsive" data-elementID="<?= $id; ?>">





                                                                <!-- New tooltip design -->





                                                                <span class="tab_one_content-<?= $indexx; ?>" data-template="true" style="display: none;">



                                                                    <?php

                                                                    $open_modify_close_name = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = 'ELEMENT_NAME' AND REG_ID = $id AND LANG_ID = '" . $lang_id . "'

");

                                                                    ?>



                                                                    <div class="main_head">

                                                                        <h6><?= encodes(mysqli_fetch_assoc($open_modify_close_name)['TEXT']); ?>
                                                                        </h6>

                                                                        <img src="<?= $img_src; ?>?time=<?= time(); ?>" alt="" class="pop_image">

                                                                    </div>



                                                                    <div class="tooltip_content_container">

                                                                        <?php



                                                                        $el_info_obj = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = 'STRATEGY_TEXT7' AND

LANG_ID = '" . $lang_id . "'

");



                                                                        $open_modify_close_name = mysqli_query($con, "SELECT * FROM `translations` WHERE

CONCEPT_NAME = 'ELEMENT_NAME' AND REG_ID = $id AND LANG_ID = '" . $lang_id . "'

");



                                                                        ?>



                                                                        <p class="el-desc design_p"> <?= $el_description; ?><a href="<?= $fetching_omc['MORE_INFO_URL']; ?>" target='_blank'>
                                                                                <?= encodes(mysqli_fetch_assoc($el_info_obj)['TEXT']); ?>
                                                                            </a></p>

                                                                        <span class="close_tooltip"><i class="fa fa-close"></i></span>



                                                                        <div class="testing accordion" id="panel<?php echo $element_id; ?>">





                                                                            <?php

                                                                            //WORK

                                                                            $params_sql = "SELECT

*

FROM

parameters

WHERE

ELEMENT_ID = $id AND ACTIVE = '1' ORDER BY ORDER_ID ASC ";



                                                                            $fetch_parameters_value = mysqli_query($con, $params_sql);



                                                                            while ($fetch_parameters = mysqli_fetch_array($fetch_parameters_value)) :

                                                                                $param_id = $fetch_parameters['PARAM_ID'];

                                                                                $fetching_param_description = mysqli_query($con, "SELECT * FROM translations WHERE  CONCEPT_NAME='PARAM_DESC' AND REG_ID=$param_id AND LANG_ID='$lang_id'");



                                                                                $param_description = mysqli_fetch_assoc($fetching_param_description)["TEXT"];



                                                                                $fetching_param_label = mysqli_query($con, "SELECT * FROM `translations` WHERE TABLE_NAME='PARAMETERS' AND CONCEPT_NAME='PARAM_NAME' AND REG_ID={$fetch_parameters['PARAM_ID']} AND LANG_ID='$lang_id'");

                                                                                $param_label = encodes(mysqli_fetch_assoc($fetching_param_label)['TEXT']);

                                                                                //INPUT CLOSE

                                                                                if ($fetch_parameters['PARAM_TYPE'] === 'INTEGER') { ?>



                                                                                    <div class="card" data-field-type='integer' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                        <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                            <h5 class="mb-0">

                                                                                                <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <label><?= $param_label . " [value]" ?></label>

                                                                                                </a>

                                                                                            </h5>

                                                                                        </div>



                                                                                        <div id="param_<?php print_r($fetch_parameters['PARAM_ID']); ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                            <div class="card-body">

                                                                                                <p><?php echo $param_description; ?> </p>

                                                                                                <input type="number" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm element_form" placeholder="<?= $param_label; ?>" value="<?= encodes($fetch_parameters['DEFAULT_PARAM']); ?>" disabled>

                                                                                                <p class="error-input-report"></p>

                                                                                            </div>

                                                                                        </div>

                                                                                    </div>



                                                                                <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'BOOL') { ?>





                                                                                    <div class="card " data-field-type='bool' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                        <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                            <h5 class="mb-0">

                                                                                                <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <label><?= $param_label . " [value]" ?></label>

                                                                                                </a>

                                                                                            </h5>

                                                                                        </div>



                                                                                        <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                            <div class="card-body form-check tooltip-check round">

                                                                                                <p><?php echo $param_description; ?>

                                                                                                    <input class="form-check-input d-none" type="checkbox" disabled id="<?= $fetch_parameters['PARAM_NAME'] ?>" name="<?= $fetch_parameters['PARAM_NAME'] ?>" <?php if ($fetch_parameters['DEFAULT_PARAM'] == '1') echo 'checked'; ?>>

                                                                                                    <label class="form-check-label"></label>

                                                                                                </p>



                                                                                            </div>

                                                                                        </div>

                                                                                    </div>







                                                                                <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'DOUBLE') { ?>



                                                                                    <div class="card" data-field-type='double' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                        <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                            <h5 class="mb-0">

                                                                                                <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <label><?= $param_label . " [value]" ?></label>

                                                                                                </a>

                                                                                            </h5>

                                                                                        </div>



                                                                                        <div id="param_<?php print_r($fetch_parameters['PARAM_ID']); ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                            <div class="card-body">

                                                                                                <p><?php echo $param_description; ?> </p>

                                                                                                <input type="text" step="any" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm element_form" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= $fetch_parameters['DEFAULT_PARAM']; ?>" disabled>

                                                                                                <p class="error-input-report"></p>

                                                                                            </div>

                                                                                        </div>

                                                                                    </div>





                                                                                <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'STRING') { ?>





                                                                                    <div class="card" data-field-type='string' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                        <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                            <h5 class="mb-0">

                                                                                                <a class="btn btn-link" rotate-icon="true" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <label><?= $param_label . " [value]" ?></label>

                                                                                                </a>

                                                                                            </h5>

                                                                                        </div>



                                                                                        <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                            <div class="card-body">

                                                                                                <p><?php echo $param_description; ?> </p>

                                                                                                <input type="text" name="<?= $fetch_parameters['PARAM_NAME'] ?>" class="form-control form-control-sm element_form" placeholder="<?= encodes($fetch_parameters['TEXT']); ?>" value="<?= $fetch_parameters['DEFAULT_PARAM']; ?>" disabled>

                                                                                                <p class="error-input-report"></p>

                                                                                            </div>

                                                                                        </div>

                                                                                    </div>



                                                                                <?php } elseif ($fetch_parameters['PARAM_TYPE'] == 'DESPLEGABLE') {

                                                                                    $param_id = $fetch_parameters['PARAM_ID'];

                                                                                    $param_name_query = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME='PARAM_OPT' and REG_ID=$param_id and LANG_ID='$lang_id'");

                                                                                    $fetch_param_name = mysqli_fetch_array($param_name_query);

                                                                                    $options = $fetch_param_name['TEXT'];

                                                                                    $values = explode(";", $options); ?>



                                                                                    <div class="card " data-field-type='dropdown' style="<?php echo $display_parameter; ?>" data-only-exit="<?php echo $fetch_parameters['ONLYEXIT']; ?>" pid="<?= $fetch_parameters['PARAM_ID']; ?>" <?php if (isset($fetch_parameters['PARENT_ID'])) : ?> parent-id="<?= $fetch_parameters['PARENT_ID']; ?>" parent-value="<?= $fetch_parameters['PARENT_VALUE']; ?>" <?php endif; ?>>

                                                                                        <div class="card-header" id="heading<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                            <h5 class="mb-0">

                                                                                                <a class="btn btn-link" data-toggle="collapse" data-target="#param_<?php echo $fetch_parameters['PARAM_ID']; ?>" aria-expanded="true" aria-controls="param_<?php echo $fetch_parameters['PARAM_ID']; ?>">

                                                                                                    <label><?= $param_label . " [value]" ?></label>

                                                                                                </a>

                                                                                            </h5>

                                                                                        </div>



                                                                                        <div id="param_<?php echo $fetch_parameters['PARAM_ID']; ?>" class="collapse" aria-labelledby="heading<?php echo $fetch_parameters['PARAM_ID']; ?>" data-parent="#panel<?php echo $element_id; ?>">

                                                                                            <div class="card-body">

                                                                                                <p><?php echo $param_description; ?> </p>

                                                                                                <select class="form-control form-control-sm get_design" disabled>

                                                                                                    <?php

                                                                                                    foreach ($values as $i => $value) {

                                                                                                        if ($fetch_parameters['DEFAULT_PARAM'] == $i) {

                                                                                                            echo "<option value='" . encodes($value) . "' selected>" . encodes($value) . "</option>";
                                                                                                        } else {

                                                                                                            echo "<option value='" . encodes($value) . "'>" . encodes($value) . "</option>";
                                                                                                        }
                                                                                                    }

                                                                                                    ?>

                                                                                                </select>

                                                                                            </div>

                                                                                        </div>

                                                                                    </div>

                                                                                <?php } ?>



                                                                            <?php endwhile; ?>

                                                                        </div>

                                                                        <div style="text-align: center;">



                                                                            <button type="submit" class="btn btn-success btn-block btn-outline-success d-none close_tooltip_save "><?= $save_text; ?></button>

                                                                        </div>



                                                                    </div>

                                                                </span>

                                                            </li>

                                                        <?php

                                                        }







                                                        ?>

                                                    <?php endif; ?>



                                                </ul>

                                            </div>

                                        </div>

                                    <?php } ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </section>

                <div class="close_element_tab" style="display: none;">

                    <i class="fa fa-remove delete_element_tab"></i>

                </div>

            </div>

            <div class="col-sm-1 add_elements_plus">

                <!-- <i class="fa fa-plus"></i>	 -->

                <nav>

                    <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist" style="margin-left: 15px;">

                        <?php



                        $group_name_results = mysqli_query($con, "

SELECT

translations.TABLE_NAME,

translations.CONCEPT_NAME,

translations.REG_ID,

element_group.GROUP_ID,

element_group.GROUP_NAME,

element_group.ORDER_ID,

element_group.ACTIVE,

translations.TEXT,

translations.LANG_ID,

translations.TRANS_ID

FROM

translations

INNER JOIN element_group ON translations.REG_ID = element_group.GROUP_ID

WHERE

translations.TABLE_NAME = 'element_group'

AND translations.CONCEPT_NAME = 'GROUP_NAME'

AND translations.LANG_ID = '$lang_id'

AND element_group.ACTIVE = '1'

ORDER BY

element_group.ORDER_ID ASC

");



                        while ($group_name_row = mysqli_fetch_array($group_name_results)) {



                            $element_group_id = $group_name_row['GROUP_ID'];

                            $id = $group_name_row['GROUP_NAME'] . '-tab';

                            $href = '#' . $group_name_row['GROUP_NAME'];

                            $group_name = $group_name_row['GROUP_NAME'];

                        ?>

                            <a class="left_panel nav-item nav-link <?php if ($group_name_row['ORDER_ID'] == 1) echo 'active'; ?>" id="<?= $id; ?>" data-toggle="tab" href="<?= $href; ?>" role="tab" aria-controls="<?= $group_name; ?>" aria-selected="true"><span style="font-size: 12px;"><?= encodes($group_name_row['TEXT']); ?></span></a>

                        <?php }

                        ?>

                    </div>

                </nav>

            </div>







            <!-- Right sidebar -->





            <div class="col-sm-11 right_side" style="padding: 0 !important; height: 567px; overflow: hidden;">

                <section id="tabs_2">

                    <nav>

                        <div class="nav nav-fill" id="nav-tab2" role="tablist">

                            <?php

                            // JFS - 09/06/2020 - control multilenguaje MORE_INFO_URL

                            //$fetch_tabs_omc = mysqli_query($con,"SELECT * FROM `elements` WHERE ELEMENT_GROUP_ID = 0 AND ELEMENT_NAME != 'SEQ' AND ELEMENT_GROUP_ID != ''");

                            $fetch_tabs_omc = mysqli_query($con, "SELECT ELEMENT_ID,

ELEMENT_GROUP_ID,

ELEMENT_NAME,

IMAGE_URL,

SOURCE_CODE,

ORDER_ID,

ACTIVE,

(SELECT text

FROM translations

WHERE reg_ID = element_id

and table_name = 'ELEMENTS'

and concept_name = 'ELEMENT_URL'

and lang_id = '$lang_id') as MORE_INFO_URL,

CUSTOM_ELEM

FROM `elements` WHERE ELEMENT_GROUP_ID = 0 AND ELEMENT_NAME != 'SEQ' AND ELEMENT_GROUP_ID != ''");

                            while ($fetching_omc = mysqli_fetch_array($fetch_tabs_omc)) {



                                $omc_id = $fetching_omc['ELEMENT_GROUP_ID'];

                                $id = $fetching_omc['ELEMENT_NAME'] . '-tab';

                                $href = '#' . $fetching_omc['ELEMENT_NAME'];



                                if ($fetching_omc['ELEMENT_NAME'] == 'OPEN') {

                                    $strategy = 'STRATEGY_OPEN';
                                } elseif ($fetching_omc['ELEMENT_NAME'] == 'CLOSE') {

                                    $strategy = 'STRATEGY_CLOSE';
                                } else {

                                    $strategy = 'STRATEGY_MODIFY';
                                }



                                $fetchcing_omc_name = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='$strategy' AND REG_ID=$omc_id AND LANG_ID='$lang_id'");



                                while ($fetched_omc_name = mysqli_fetch_array($fetchcing_omc_name)) {

                                    $translated_omc_name = encodes($fetched_omc_name['TEXT']); ?>



                                    <a class="nav-item nav-link

<?php if ($fetching_omc['ELEMENT_ID'] == 8) echo 'active'; ?>" data-icon-id="<?= $fetching_omc['ELEMENT_ID']; ?>" id="<?= $id ?>" data-toggle="tab" href="<?= $href; ?>" role="tab" aria-controls="<?= $fetching_omc['ELEMENT_NAME']; ?>" aria-selected="false" <?php if ($fetching_omc['ELEMENT_NAME'] == 'MODIFY') { ?> style="display: none;" <?php } ?>> <?= $translated_omc_name; ?></a>



                            <?php }
                            } ?>

                        </div>

                    </nav>

                    <div class="tab-content px-3 px-sm-0" id="nav-tabContent2">

                        <?php

                        $fetch_rule_text = mysqli_query($con, "SELECT TEXT FROM `translations` WHERE CONCEPT_NAME='STRATEGY_TEXT10' AND LANG_ID='$lang_id'");
                        $fetched_rule_text = mysqli_fetch_array($fetch_rule_text);

                        $fetch_open_tooltip_text = mysqli_query($con, "SELECT TEXT FROM `translations` WHERE CONCEPT_NAME='STRATEGY_TEXT12' AND LANG_ID='$lang_id'");
                        $fetched_open_tp_txt = mysqli_fetch_array($fetch_open_tooltip_text);

                        $fetch_close_tooltip_text = mysqli_query($con, "SELECT TEXT FROM `translations` WHERE CONCEPT_NAME='STRATEGY_TEXT13' AND LANG_ID='$lang_id'");
                        $fetched_close_tp_txt = mysqli_fetch_array($fetch_close_tooltip_text);

                        // JFS - 09/06/2020 - control multilenguaje MORE_INFO_URL

                        //$fetch_tabs_omc = mysqli_query($con,"SELECT * FROM `elements` WHERE ELEMENT_GROUP_ID = 0 AND ELEMENT_NAME != 'SEQ' AND ELEMENT_GROUP_ID != ''");

                        $fetch_tabs_omc = mysqli_query($con, "SELECT ELEMENT_ID,ELEMENT_GROUP_ID,ELEMENT_NAME,IMAGE_URL,SOURCE_CODE,ORDER_ID,ACTIVE,
(SELECT text

FROM translations
WHERE reg_ID = element_id
and table_name = 'ELEMENTS'
and concept_name = 'ELEMENT_URL'
and lang_id = '$lang_id') as MORE_INFO_URL,
CUSTOM_ELEM
FROM `elements` WHERE ELEMENT_GROUP_ID = 0 AND ELEMENT_NAME != 'SEQ' AND ELEMENT_GROUP_ID != ''");



                        while ($fetching_omc = mysqli_fetch_array($fetch_tabs_omc)) {



                            $id = $fetching_omc['ELEMENT_ID'];

                            $href = $fetching_omc['ELEMENT_NAME'];

                            $img = $fetching_omc['IMAGE_URL'];

                            $more_info_url = $fetching_omc['MORE_INFO_URL'];



                        ?>



                            <div class="omc tab-pane fade <?php if ($fetching_omc['ELEMENT_ID'] == 8) echo 'show active'; ?>" id="<?= $href; ?>" role="tabpanel" aria-labelledby="<?= $href ?>" data-id-omc='<?= $fetching_omc['ELEMENT_NAME']; ?>' data-tab_id='<?= $fetching_omc['ELEMENT_ID']; ?>'>
                                <table class=" table order-list" style="height: 500px; margin: 0;">
                                    <tbody>
                                        <tr class="flex-column">
                                            <th class="p-0">
                                                <i class="material-icons md-tooltip--right delete-icon" data-md-tooltip="<?php echo ($fetching_omc['ELEMENT_ID'] == 8 ? encodes($fetched_open_tp_txt['TEXT']) : encodes($fetched_close_tp_txt['TEXT'])); ?>" style="font-size:32px; cursor: pointer;">delete</i>
                                                <p class="rule-title" display-text="">
                                                    <?= encodes($fetched_rule_text['TEXT']); ?> 1</p>

                                            </th>
                                            <td class="gallery_new" style="margin-left: 25px;">
                                                <div class="ui-helper-reset gallery-rep">
                                                    <ul id="trash" class="trash ui-widget-content ui-state-default" style="width:740px ;overflow-x: auto;">
                                                        <li class="dashed_image_li d-sort display_none">

                                                            <p style="font-size: 12px">DASHED iMAGE</p>

                                                            <?php
                                                            $fetchcing_drag_text = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='STRATEGY_TEXT4' AND LANG_ID='$lang_id'");
                                                            ?>

                                                            <div class="dashed-div">
                                                                <div style="overflow: hidden; height: 45px;">
                                                                    <?= encodes(mysqli_fetch_array($fetchcing_drag_text)['TEXT']); ?>
                                                                </div>
                                                            </div>

                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="add_new_stage">
                                            <td style="margin-left: 25px; ">
                                                <ul class="trash_dont_accept" style="display: none;">
                                                    <li class="dashed_image_li d-sort display_none">
                                                        <p style="font-size: 12px">DASHED iMAGE</p>
                                                        <?php

                                                        $fetchcing_add_stage_name = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='STRATEGY_TEXT2' AND LANG_ID='$lang_id'");
                                                        $add_new_b = encodes(mysqli_fetch_array($fetchcing_add_stage_name)['TEXT']);
                                                        ?>
                                                        <div class="dashed-div-2 ">
                                                            <div tyle="overflow: hidden; height: 79px; text-align: center;">
                                                                <?= ucwords($add_new_b); ?><h3 class="row_design">+</h3>
                                                            </div>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot style="visibility:  hidden;">
                                        <tr>
                                            <td colspan="5" style="text-align: left;">

                                                <?php

                                                $fetchcing_add_stage_name = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='STRATEGY_TEXT2' AND LANG_ID='$lang_id'");

                                                $add_new_b = encodes(mysqli_fetch_array($fetchcing_add_stage_name)['TEXT']);

                                                ?>
                                                <button class="button arrow" id="addrow" rule-text="<?= encodes($fetched_rule_text['TEXT']); ?>"><?= ucwords($add_new_b); ?></button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                        <?php } ?>

                    </div>

                </section>



                <br>

            </div>

        </div>

    </div>

</div>

</div>



<div class="validate-tab">

    <div class="container-fluid h-100 py-6">

        <div class="row">

            <div class="col-sm-8" style="border: 3px solid #27a5df;border-radius: 25px;background: #ecf9ff; height: 530px;">

                <div class="validatingGraph">

                    <div style="position: relative;top: 50%;">

                        <?php

                        $getWelcomValidateText = mysqli_query($con, "SELECT * FROM `translations` WHERE TABLE_NAME='(SCREEN VAL)' AND CONCEPT_NAME='WELCOME_TEXT' AND REG_ID=0 AND LANG_ID='$lang_id'");

                        $welcome_validate_trans = encodes(mysqli_fetch_assoc($getWelcomValidateText)['TEXT']);

                        ?>

                        <p><?= $welcome_validate_trans ?></p>

                    </div>

                </div>

                <div id="chartContainer" style="height: auto; margin-top: 30px; display: none;"></div>

                <div id="infoOperations">

                    <div class="table-responsive">

                        <table class="table table-striped table-bordered info-operations">

                            <thead>

                                <tr>

                                    <th>#</th>

                                    <?php

                                    $ops_column_names = [];

                                    $ops_columns_query = mysqli_query($con, "SELECT CONCEPT_NAME, TEXT FROM translations WHERE CONCEPT_NAME like 'OPS_TABLE_COLUMN%' and LANG_ID = '$lang_id'");

                                    while ($row = mysqli_fetch_assoc($ops_columns_query)) {

                                        $ops_column_names[$row['CONCEPT_NAME']] = $row['TEXT'];
                                    }



                                    $opFields = [

                                        'tipoOP' => $ops_column_names['OPS_TABLE_COLUMN1'],

                                        'fechaIni' => $ops_column_names['OPS_TABLE_COLUMN2'],

                                        'precioIni' => $ops_column_names['OPS_TABLE_COLUMN3'],

                                        'fechaFin' => $ops_column_names['OPS_TABLE_COLUMN4'],

                                        'precioFin' => $ops_column_names['OPS_TABLE_COLUMN5'],

                                        'OrderProf' => $ops_column_names['OPS_TABLE_COLUMN6'],

                                        'comision' => $ops_column_names['OPS_TABLE_COLUMN7'],

                                        'Swap' => $ops_column_names['OPS_TABLE_COLUMN8']

                                    ];



                                    foreach ($opFields as $key => $value) {

                                        echo '<th name="' . $key . '">' . $value . '</th>';
                                    }

                                    ?>

                                </tr>

                            </thead>

                            <tbody></tbody>

                        </table>

                    </div>

                    <?php

                    $tooltip_texts = [];

                    $tooltip_query = mysqli_query($con, "SELECT CONCEPT_NAME, TEXT FROM translations WHERE CONCEPT_NAME like 'POINT_TOOLTIP_TEXT%' and LANG_ID = '$lang_id'");

                    while ($row = mysqli_fetch_assoc($tooltip_query)) {

                        $tooltip_texts[$row['CONCEPT_NAME']] = $row['TEXT'];
                    }



                    $pointToolTipTexts = [

                        'num' => $tooltip_texts['POINT_TOOLTIP_TEXT1'],

                        'date' => $tooltip_texts['POINT_TOOLTIP_TEXT2'],

                        'hour' => $tooltip_texts['POINT_TOOLTIP_TEXT3'],

                        'profit' => $ops_column_names['OPS_TABLE_COLUMN6'],

                        'balance' => $tooltip_texts['POINT_TOOLTIP_TEXT4']

                    ];

                    ?>

                    <div class="d-none tp-texts"><?php echo json_encode($pointToolTipTexts); ?></div>

                </div>
                <div id="tradingView" class="col-sm-12" style="height: auto;display:none;">
                    <iframe id="tradingView-content" style="height: 500px; margin-top: 20px; border:none;" class="col-sm-12" src="" title="Trading View React"></iframe>
                </div>

                <?php
                $toggle_btn_texts = [];
                $toggle_btn_query = mysqli_query($con, "SELECT CONCEPT_NAME, TEXT FROM translations WHERE CONCEPT_NAME like 'TOGGLE_BUTTON_TEXT%' and LANG_ID = '$lang_id'");
                while ($row = mysqli_fetch_assoc($toggle_btn_query)) {
                    $toggle_btn_texts[$row['CONCEPT_NAME']] = $row['TEXT'];
                }
                ?>
                <div class="toggle-buttons d-none">
                    <label>
                        <input type="radio" name="toggle" checked="true">
                        <span class="btn btn-outline-primary"><?php echo $toggle_btn_texts['TOGGLE_BUTTON_TEXT1']; ?></span>
                    </label>
                    <label>
                        <input type="radio" name="toggle">
                        <span class="btn btn-outline-secondary"><?php echo $toggle_btn_texts['TOGGLE_BUTTON_TEXT2']; ?></span>
                    </label>
                    <label>
                        <input type="radio" name="toggle">
                        <span class="btn btn-outline-secondary">Trading View</span>
                    </label>
                </div>

                <?php $strategyText = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='STRATEGY_TEXT3' AND LANG_ID='$lang_id'"); ?>
            </div>

            <div class="col-sm-4 d-flex flex-column" style="height: 530px;">
                <form class="validate-form">
                    <div class="d-flex">
                        <?php
                        // Get Date format depending on Language
                        $DateFormat = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='DATEFORMAT_TEXT' AND LANG_ID='$lang_id'");
                        $format = strtolower(encodes(mysqli_fetch_assoc($DateFormat)['TEXT']));
                        if ($format == 'dd/mm/yyyy') {
                            // Spanish Format
                            $php_dateFormat = "d/m/Y";
                            $sql_dateFormat = "%d/%m/%Y";
                        } else {
                            // Standard Format
                            $php_dateFormat = "m/d/Y";
                            $sql_dateFormat = "%m/%d/%Y";
                        }
                        ?>
                        <div class="form-group option-1">
                            <?php $balanceText = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='POINT_TOOLTIP_TEXT4' AND LANG_ID='$lang_id'"); ?>
                            <label for="balance"><?= encodes(mysqli_fetch_assoc($balanceText)['TEXT']); ?>:</label>
                            <div>
                                <input class="form-control form-control-sm balanceInput" type="number" name="balance" id="balance" value="10000" placeholder="10000" min="300">
                                <?php $more_info_text = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME = 'TEXT_MORE_INFO' AND LANG_ID = '$lang_id'"); ?>
                            </div>
                            <p id="more-info"><?= encodes(mysqli_fetch_assoc($more_info_text)['TEXT']); ?></p>
                        </div>
                        <div class="form-group option-1">
                            <?php $currency_text = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='CURRENCY' AND LANG_ID='$lang_id'"); ?>
                            <label for="currency"><?= encodes(mysqli_fetch_assoc($currency_text)['TEXT']); ?>:</label>
                            <div>
                                <select class="form-control form-control-sm" name="currency" id="currency">
                                    <?php
                                    $currencies = $con->query("SELECT currency FROM currencies");
                                    while ($currency = mysqli_fetch_assoc($currencies)) {
                                    ?>
                                        <option value="<?= $currency['currency'] ?>"><?= $currency['currency'] ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group option-1">
                            <?php $tickerText = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='TICKER_TEXT' AND LANG_ID='$lang_id'"); // Get text from DB depending on lang.
                            ?>
                            <label for="ticket"><?= encodes(mysqli_fetch_assoc($tickerText)['TEXT']); ?>:</label>
                            <div>
                                <select id="ticket" class="form-control form-control-sm" name="ticker">
                                    <?php
                                    $tickers = array();
                                    $selectTickers = $con->query("SELECT IFNULL(display_name,TICKER_NAME) AS TICKER_NAME, TICKER_ID, DATE_FORMAT(START_DATE, '$sql_dateFormat') AS START_DATE, DATE_FORMAT(END_DATE, '$sql_dateFormat') AS END_DATE, PRICE_POINT, CONCAT(NOMINAL_LOT, ' ', NOMINAL_CURRENCY) AS NOMINAL_LOT, LOT_MIN, LOT_MAX, LOT_STEP, CONCAT(LOT_COMMISSION, ' ', COMMISSION_CURRENCY) AS LOT_COMMISSION, LEVERAGE, GMT,SPREAD  FROM ticker");
                                    $index = 0;
                                    while ($timeTicker = mysqli_fetch_assoc($selectTickers)) {
                                    ?>

                                        <?php
                                        // $firstStartDate -> Variable donde se guardará la fecha inicio del primer activo (Antiguo uso).
                                        // $firstStartDate -> Variable que indica el inicio de la validación, por defecto 01/01/2020 (28/12/2020).
                                        // $firstEndDate -> Variable donde se guardará la fecha fin del primer activo
                                        $ticker_id = $timeTicker['TICKER_ID'];
                                        if ($index == 0) {
                                            $firstStartDate = $timeTicker['START_DATE'];
                                            $firstEndDate = $timeTicker['END_DATE'];
                                        }
                                        if ($user_mode['TYPE_USER'] == 'F') {

                                            //$freemium_user = mysqli_query($con, "SELECT * FROM `FREE_AVALIABLE_ITEMS` WHERE ID = '$ticker_id' AND TABLE_ID = 'ticker'");
                                            $freemium_user = mysqli_query($con, "SELECT table_id as 'TABLE', id as 'ID' FROM `FREE_AVALIABLE_ITEMS` WHERE ID = '$ticker_id' AND TABLE_ID = 'ticker'
                                                                                  union
                                                                                 select table_id as 'TABLE', e.TICKER_ID as 'ID' from ticker e, FREE_AVALIABLE_USERS t where t.USER_ID = '$user_id' and e.TICKER_ID = '$ticker_id' and t.table_id = 'ticker'");


                                            $rows = mysqli_fetch_array($freemium_user);
                                            $freemium_ticker_table = $rows['TABLE'];
                                            $freemium_ticker_id = $rows['ID'];

                                            if ($freemium_ticker_id == $ticker_id) {
                                        ?>
                                                <!-- echo $timeTicker['TICKER_ID']; -->
                                                <option value="<?php echo $timeTicker['TICKER_ID']; ?>" start-date="<?= $timeTicker['START_DATE']; ?>" end-date="<?= $timeTicker['END_DATE']; ?>" selected>
                                                    <?php echo $timeTicker['TICKER_NAME']; ?>
                                                </option>
                                            <?php } else { ?>
                                                <option value="disabled" class="disabled_option" start-date="<?= $timeTicker['START_DATE']; ?>" end-date="<?= $timeTicker['END_DATE']; ?>">
                                                    <?php
                                                    echo $timeTicker['TICKER_NAME'];
                                                    $timeTicker['TICKER_ID'];
                                                    ?>
                                                    <img src="images/candado.png" width="20" height="20" />
                                                </option>
                                            <?php
                                            }
                                        } else {
                                            ?><option value="<?php echo $timeTicker['TICKER_ID']; ?>" start-date="<?= $timeTicker['START_DATE']; ?>" end-date="<?= $timeTicker['END_DATE']; ?>">
                                                <?php echo $timeTicker['TICKER_NAME']; ?>
                                            </option>
                                    <?php
                                        }
                                        $timeTicker = array_change_key_case($timeTicker);
                                        array_push($tickers, $timeTicker);
                                        $index++;
                                    }
                                    ?>
                                </select>
                                <div class="d-none"><?= json_encode($tickers); ?></div>
                            </div>
                        </div>

                        <div class="form-group option-1 flex-grow-1">

                            <label for="timeframe">Timeframe: </label>

                            <div>

                                <select id="timeframe" class="form-control form-control-sm" name="time_frame">
                                    <?php

                                    $selectTimeFrame = $con->query("SELECT * FROM timeframes WHERE LANGUAJE_id = '$lang_id'");

                                    while ($timeframe = mysqli_fetch_array($selectTimeFrame)) {

                                        if ($user_mode['TYPE_USER'] == 'F') {
                                            $TF_ID = $timeframe['TF_ID'];
                                            //$freemium_user = mysqli_query($con, "SELECT * FROM `FREE_AVALIABLE_ITEMS` WHERE ID = '$TF_ID' AND TABLE_ID = 'TIMEFRAMES'");

                                            $freemium_user = mysqli_query($con, "SELECT table_id as 'TABLE', id as 'ID' FROM `FREE_AVALIABLE_ITEMS` WHERE ID = '$TF_ID' AND TABLE_ID = 'TIMEFRAMES'
                                            union
                                            select table_id as 'TABLE', e.TF_ID as 'ID' from timeframes e, FREE_AVALIABLE_USERS t where t.USER_ID = '$user_id' and e.TF_ID = '$TF_ID' and t.table_id = 'TIMEFRAMES'");


                                            $rows = mysqli_fetch_array($freemium_user);
                                            $freemium_timeframe_table = $rows['TABLE'];
                                            $freemium_timeframe_id = $rows['ID'];

                                            if ($TF_ID == $freemium_timeframe_id) {
                                    ?>
                                                <option value="<?php echo $timeframe['TF_ID']; ?>" selected>
                                                    <?php echo $timeframe['TF_NAME']; ?></option>
                                            <?php
                                            } else {
                                            ?>
                                                <option value="disabled" class="disabled_option">
                                                    <?php echo $timeframe['TF_NAME']; ?></option>
                                            <?php
                                            }
                                        } else {
                                            ?>
                                            <option value="<?php echo $timeframe['TF_ID']; ?>">
                                                <?php echo $timeframe['TF_NAME']; ?></option>
                                    <?php
                                        }
                                    }

                                    ?>

                                </select>
                            </div>
                        </div>
                    </div>

                    <?php

                    $errDateText = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='CALENDAR_ERROR1' AND LANG_ID='$lang_id'");

                    $errDateText = encodes(mysqli_fetch_assoc($errDateText)['TEXT']);

                    ?>
                    <div class="d-flex">
                        <div class="form-group option-2 w-50">

                            <?php

                            $starttime = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='VALIDATION_PARAMETERS1' AND LANG_ID='$lang_id'");

                            $starttime = encodes(mysqli_fetch_assoc($starttime)['TEXT']);

                            ?>

                            <label for="start-time"><?php echo $starttime ?></label>
                            <div class="calendar-container fixcalendarformat">
                                <div class="calendar d-flex flex-row">
                                    <?php
                                    // Fecha inicio
                                    //$yearDiff = date('Y')-2017;
                                    //$start_date = "01/01/".(date('Y')-$yearDiff);
                                    // Fecha fin
                                    $end_date = date($php_dateFormat);
                                    ?>
                                    <input data-format="<?= $format ?>" type="text" class="datepicker-starttime form-control form-control-sm" name="start-date" id="start-time" data-date-start-date="<?= $firstStartDate; ?>" data-date-end-date="<?= $end_date; ?>" value="01/01/2020" />
                                    <i class="fa fa-calendar" aria-hidden="true"></i>
                                </div>

                                <p style="display: none;" text="<?= $errDateText; ?>"></p>

                                <ul id="calendarErrors" class="d-none">
                                    <?php
                                    $error_texts = mysqli_query($con, "SELECT TEXT FROM `translations` WHERE CONCEPT_NAME='CALENDAR_ERRORS' AND LANG_ID='$lang_id'");
                                    while ($text = mysqli_fetch_array($error_texts)) {
                                    ?>
                                        <li><?= encodes($text['TEXT']); ?></li>
                                    <?php
                                    }
                                    ?>
                                </ul>
                                <ul id="calendarErrors2" class="d-none">
                                    <?php
                                    $error_texts = mysqli_query($con, "SELECT TEXT FROM `translations` WHERE CONCEPT_NAME='CALENDAR_ERRORS2' AND LANG_ID='$lang_id'");
                                    while ($text = mysqli_fetch_array($error_texts)) {
                                    ?>
                                        <li><?= encodes($text['TEXT']); ?></li>
                                    <?php
                                    }
                                    ?>
                                </ul>
                                <ul id="calendarErrors3" class="d-none">
                                    <?php
                                    $error_texts = mysqli_query($con, "SELECT TEXT FROM `translations` WHERE CONCEPT_NAME='CALENDAR_ERRORS3' AND LANG_ID='$lang_id'");
                                    while ($text = mysqli_fetch_array($error_texts)) {
                                    ?>
                                        <li><?= encodes($text['TEXT']); ?></li>
                                    <?php
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                        <div class="form-group option-2 fixmargincalendar w-50">
                            <?php
                            $endtime = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='VALIDATION_PARAMETERS2' AND LANG_ID='$lang_id'");
                            $endtime = encodes(mysqli_fetch_assoc($endtime)['TEXT']);
                            ?>
                            <label for="end-time"><?php echo $endtime ?></label>
                            <div class="calendar-container">
                                <div class="calendar d-flex flex-row">
                                    <input data-format="<?= $format ?>" type="text" class="datepicker-endtime form-control form-control-sm" name="end-date" data-date-start-date="<?= $firstStartDate; ?>" data-date-end-date="<?= $end_date; ?>" value="<?= $firstEndDate; ?>" />
                                    <i class="fa fa-calendar" aria-hidden="true"></i>
                                </div>
                                <p style="display: none;" text="<?= $errDateText; ?>"></p>
                            </div>
                        </div>
                    </div>
                    <!--  Extra Fields -->
                    <?php
                    $user_id = get_current_user_id();
                    $strategy_id = mysqli_query($con, "SELECT sesion_id FROM session_strategy WHERE user_id = $user_id");
                    $id = mysqli_fetch_assoc($strategy_id);
                    ?>
                    <input type="hidden" name="user_id" value="<?= $user_id ?>">
                    <input type="hidden" name="session_strategy_id" value="<?php echo $id['sesion_id']; ?>">
                    <div class="submit-btn">
                        <button type="button" id="validate-next" class="btn" onclick="this.disabled=true" data-action="<?= $actual_link; ?>?action=validate_data"><?= $validate_trans ?></button>
                    </div>
                </form>



                <div class="validateStrategyScreen">
                    <?php $resultTxt = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME = 'TEXT_RESULT' and LANG_ID = '$lang_id'"); ?>
                    <p><?= encodes(mysqli_fetch_assoc($resultTxt)['TEXT']); ?></p>
                    <div class="validateScreen">
                        <p><?= $welcome_validate_trans ?></p>
                    </div>
                    <div class="validatingScreen" style="display: none;">
                        <center>
                            <?php
                            $WaitingValidationText = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='WAITING_TEXT' AND LANG_ID='$lang_id'");
                            ?>

                            <p><?= encodes(mysqli_fetch_assoc($WaitingValidationText)['TEXT']) ?></p>

                            <i class="fa fa-refresh fa-spin" style="font-size:24px"></i>



                            <?php $cancel_text = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME = 'MODAL_CANCEL_BUTTON' and LANG_ID = '$lang_id'"); ?>

                            <button type="button" data-action="<?= $actual_link; ?>?action=cancel_validation" id="cancel-validattion"><?= encodes(mysqli_fetch_assoc($cancel_text)['TEXT']) ?></button>



                            <!-- <img src="images/ajax-loader-green.gif"> -->

                        </center>

                    </div>

                    <div class="validatedScreen">

                        <center>

                        </center>

                        <p>Validated</p>

                    </div>

                </div>

            </div>

            <?php /*<div class="col-sm-12">



<button type="button" class="btn summary-btn" style="float: left;">  <span><?= encodes(mysqli_fetch_assoc($strategyText)['TEXT']); ?></span>
                </button>

            </div> */ ?>

        </div>

    </div>

</div>









<?php

    // System defitnitoin Heading

    $fetchcing_defintition_heading = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='STRAT_DEF_TEXT' AND LANG_ID='$lang_id'");

    $definition_heading = encodes(mysqli_fetch_assoc($fetchcing_defintition_heading)['TEXT']);



    // Agree condition text

    $fetchcing_condi_text = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='AGREE_COND' AND LANG_ID='$lang_id'");

    $condi_text = encodes(mysqli_fetch_assoc($fetchcing_condi_text)['TEXT']);



    $exploded = explode(" ", $condi_text);



    if ($lang_id == 'ES') {

        $link_text = $exploded[0] . " " . $exploded[1] . " " . $exploded[2] . " " . $exploded[3] . " " . $exploded[4] . " ";

        $link = $exploded[5] . " " . $exploded[6];
    } else {

        $link_text = $exploded[0] . " " . $exploded[1] . " " . $exploded[2] . " " . $exploded[3] . " ";

        $link = $exploded[4] . " " . $exploded[5] . " " . $exploded[6] . " " . $exploded[7];
    }



    // Link text



    $fetchcing_condi_text_url = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='CONDITION_URL' AND LANG_ID='$lang_id'");

    $condi_text_url = encodes(mysqli_fetch_assoc($fetchcing_condi_text_url)['TEXT']);



    // INI - CGM - 08/06/2020 - Añadimos campos de BBDD para cargar texto de ayuda descarga según idioma



    $fetchcing_text_help_download  = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='HELP_DOWN_TEXT' AND LANG_ID='$lang_id'");

    $text_help_download = encodes(mysqli_fetch_assoc($fetchcing_text_help_download)['TEXT']);



    $fetchcing_url_help_download  = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='HELP_DOWN_URL' AND LANG_ID='$lang_id'");

    $url_help_download = encodes(mysqli_fetch_assoc($fetchcing_url_help_download)['TEXT']);



    // FIN - CGM - 08/06/2020 - Añadimos campos de BBDD para cargar texto de ayuda descarga según idioma



    // Understand System definitoin

    $sys_def_text_1 = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='UNDERSTAND_STRAT_TEXT' AND LANG_ID='$lang_id'");

    $sys_def_text = encodes(mysqli_fetch_assoc($sys_def_text_1)['TEXT']);



    // Download Button

    //$download_btn_text = mysqli_query($con,"SELECT * FROM `translations` WHERE CONCEPT_NAME='DOWN_BUTTON' AND LANG_ID='$lang_id'");

    //$down_btn_trans = encodes(mysqli_fetch_assoc($download_btn_text)['TEXT']);





    // Download Button2

    //$download_btn_text2 = mysqli_query($con,"SELECT * FROM `translations` WHERE CONCEPT_NAME='DOWN_DEMO' AND LANG_ID='$lang_id'");

    //$down_btn_trans2 = encodes(mysqli_fetch_assoc($download_btn_text2)['TEXT']);



    // Download Button3

    //$download_btn_text3 = mysqli_query($con,"SELECT * FROM `translations` WHERE CONCEPT_NAME='DOWN_PREM' AND LANG_ID='$lang_id'");

    //$down_btn_trans3 = encodes(mysqli_fetch_assoc($download_btn_text3)['TEXT']);







?>

<div class="download-tab">
    <div class="container-fluid h-100 py-6">
        <div class="row">
            <div class="col-sm-4">
                <h3 style="margin-left: 3%;"><?= $definition_heading ?></h3>
                <div class="system-defination"></div>
            </div>

            <div class="col-sm-8">
                <div class="right-options" id="right_compile">
                    <?php
                    $downloadReadyText = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='DOWNLOAD_TEXT1' AND LANG_ID='$lang_id'");
                    $downloadReadyURL = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='DOWNLOAD_TEXT2' AND LANG_ID='$lang_id'");
                    ?>
                    <form>
                        <div>
                            <?php
                            $downloadReadyText = encodes(mysqli_fetch_assoc($downloadReadyText)['TEXT']);
                            $downloadReadyURL = encodes(mysqli_fetch_assoc($downloadReadyURL)['TEXT']);
                            echo $downloadReadyText . " " . $downloadReadyURL;
                            ?>
                        </div>
                        <a>
                            <image src="images/TE_to_MT4.png" style="margin-top: 10%;"></image>
                        </a>
                        <div class="form-group" style="margin-top:10%;">
                            <input type="checkbox" name="read_accept">
                            <?= $sys_def_text ?>
                        </div>

                    </form>
                </div>

                <?php if ($is_iron != 0) { ?>
                    <div class="iron-lab" id="iron_lab">
                        <a href="https://www.ironfx.com.bm/es/register?utm_source=13080679&utm_medium=ib_link&utm_campaign=IB" target="_blank">Abre tu cuenta Live IronFX </a> y desbloquea GRATIS descargas ilimitadas!
                    </div>
                <?php } ?>

                <?php if ($is_iron != 0) { ?>
                    <div class="right-options2">
                        <a href="https://www.ironfx.com.bm/es/register?utm_source=13080679&utm_medium=ib_link&utm_campaign=IB" target="_blank">
                            <image src="images/down-button-iron.png"></image>
                        </a>
                    </div>
                    <?php }

                // CGM - 08/6/2020 - Añadir botón de ayuda para descarga con URL

                if ($user_mode['TYPE_USER'] == 'F') {
                    // echo "testing";
                    $url_down_button_f = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='URL_DOWN_BUTTON_F' AND LANG_ID='$lang_id'");
                    $url_down_button_f = encodes(mysqli_fetch_assoc($url_down_button_f)['TEXT']);
                    // echo $url_down_button_f;
                    if ($is_iron == 0) { ?>
                        <div style="text-align:center" class="right-options2" id="right_download">
                            <?php
                            $avisameReadyText = '¿Quieres que te avisemos cuando tu estrategia de una señal? ';
                            $avisameReadyURL = '<a href="avisador.php" target="_blank"> haz clik aqui </a>';
                            ?>
                            <?php echo $avisameReadyText; ?>
                            <a title="Avisame" href="avisador.php" target="_blank"><img src="images/avisame.png" alt="avisame" /></a>
                            <br>
                            <image src="<?php echo $url_down_button_f; ?>" style="display:block;" class="btn download_button" id="download-premium" disabled></image>
                            <?php echo $text_help_download . " " . $url_help_download; ?>
                        </div>
                    <?php
                    }
                } else {
                    // echo "else_testing";
                    $url_down_button = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='URL_DOWN_BUTTON' AND LANG_ID='$lang_id'");
                    $url_down_button = encodes(mysqli_fetch_assoc($url_down_button)['TEXT']);
                    // echo $url_down_button;
                    if ($is_iron == 0) { ?>
                        <div style="text-align:center" class="right-options2" id="right_download">
                            <?php
                            $avisameReadyText = '¿Quieres que te avisemos cuando tu estrategia de una señal? ';
                            echo $avisameReadyText;
                            ?>
                            <a title="Avisame" href="avisador.php" target="_blank"><img src="images/avisame.png" alt="avisame" /></a>
                            <br>
                            <image src="<?php echo $url_down_button; ?>" style="display:block;" class="btn download_pay" id="download-premium"></image><?php echo $text_help_download . " " . $url_help_download; ?>
                        </div>
                <?php
                    }
                }
                ?>
                <div class="right-options">

                </div>
            </div>
        </div>
    </div>
</div>

<img class="payment_loader" src="images/25.gif" style="display: none; position: absolute; top: 50%; left: 65%">
<div class="row system_defination_btn">
    <div class="col-sm-12">
        <?php
        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        /**
         * Variable para determinar si la estrategia se está cargando
         * @var Bool
         */
        $loadStrategy = false;
        if (isset($myStrategy)) {
            $actual_link = strtok($actual_link, '?');
            $loadStrategy = true;
        } ?>
        <input type="hidden" name="siteLink" value="<?= $actual_link ?>?action=chart_data" data-validateId="">
        <input type="hidden" name="check_link_status" value="<?= $actual_link ?>?action=link_status" data-validateId="">
        <input type="hidden" name="session_compiled" value="<?= $actual_link ?>?action=session_compiled" data-validateId="">
        <input type="hidden" name="lastStrategy" value="<?= $actual_link ?>?action=lastStrategy">
        <input type="hidden" name="user_logged" value="<?= $actual_link ?>?action=user_logged">
        <input type="hidden" name="my-account" value="<?= home_url($path = '/', $scheme = "https") . 'mi-tradeasy/'; ?>">
        <input type="hidden" name="logout_url" value="<?= home_url($path = '/', $scheme = "https") . '?cerrar-sesion=true'; ?>">
        <button type="button" id="save_data" class="btn btn-success" data-action="<?= $actual_link; ?>?action=save_data" style="float: right;"> <img src="images/ajax-loader.gif" style="display: none;"> <span>Save Strategy</span> </button>

        <?php $strategy_summary_text = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='STRATEGY_TEXT3' AND LANG_ID='$lang_id'"); ?>

        <button type="button" id="close_tooltipseter_" class="btn build-next" data-tooltip-content="#tooltip_content_definition" data-action="<?= $actual_link; ?>?action=system_defination" style="margin-top: 20px; margin-left: 7px;float: left;">
            <span><?= encodes(mysqli_fetch_assoc($strategy_summary_text)['TEXT']); ?></span> </button>

        <?php $descButtonText = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME='STRATEGY_TEXT14' AND LANG_ID='$lang_id'"); ?>
        <button type="button" id="desc_button" tab-Info="builder" class="btn desc-button-color" style="margin-top: 20px; margin-right: 2%;float: right;">
            <span><?= encodes(mysqli_fetch_assoc($descButtonText)['TEXT']); ?></span> </button>
        <br>
    </div>
</div>

<?php
    $error_data = mysqli_query($con, "SELECT * FROM `translations` WHERE CONCEPT_NAME = 'TEXT_ERROR'  AND LANG_ID = '" . $lang_id . "'");
    $error_code_0 = mysqli_query($con, "SELECT * FROM `ERROR` WHERE ERROR_CODE = 0  AND LANG_ID = '" . $lang_id . "'");
    $error_code_1 = mysqli_query($con, "SELECT * FROM `ERROR` WHERE ERROR_CODE = 1  AND LANG_ID = '" . $lang_id . "'");
    $error_code_2 = mysqli_query($con, "SELECT * FROM `ERROR` WHERE ERROR_CODE = 2  AND LANG_ID = '" . $lang_id . "'");
    $error_code_3 = mysqli_query($con, "SELECT * FROM `ERROR` WHERE ERROR_CODE = 3  AND LANG_ID = '" . $lang_id . "'");
?>

<input type="hidden" class="error_code_data" data-error="<?= encodes(mysqli_fetch_assoc($error_data)['TEXT']) ?>" data-error-0="<?= encodes(mysqli_fetch_assoc($error_code_0)['ERROR_DESC']) ?>" data-error-1="<?= encodes(mysqli_fetch_assoc($error_code_1)['ERROR_DESC']) ?>" data-error-2="<?= encodes(mysqli_fetch_assoc($error_code_2)['ERROR_DESC']) ?>" data-error-3="<?= encodes(mysqli_fetch_assoc($error_code_3)['ERROR_DESC']) ?>">
<?php $error_text_1 = mysqli_query($con, "SELECT TEXT FROM translations WHERE CONCEPT_NAME = 'STRATEGY_TEXT15' AND LANG_ID = '$lang_id'"); ?>

<input type="hidden" id="errorsModal" error-1="<?= encodes(mysqli_fetch_assoc($error_text_1)['TEXT']); ?>">
<?php
    // Textos de error excepciones de parámetros
    $conc_name_length = strlen("PARAM_EXCEPTION_TEXT");
    $params_exceptions_texts = mysqli_query($con, "SELECT TEXT, CONCEPT_NAME FROM translations WHERE CONCEPT_NAME LIKE 'PARAM_EXCEPTION_TEXT%' AND LANG_ID = '$lang_id'");
    $array_texts = array();
    while ($row = mysqli_fetch_array($params_exceptions_texts)) {
        $key = substr($row['CONCEPT_NAME'], $conc_name_length);
        $array_texts[$key] = encodes($row['TEXT']);
    }
    // Excepciones de los parámetros
    $params_exceptions = mysqli_query($con, "SELECT p.ELEMENT_ID, pe.* FROM params_exceptions pe, parameters p WHERE p.PARAM_ID = pe.PARAM_ID");
    $array_exceptions = array();
    while ($row = mysqli_fetch_array($params_exceptions)) {
        $text = $array_texts[strval($row['PARAM_EXCEPTION_TEXT_NUM'])];
        $text = isset($text) ? $text : 'Error';
        $exception = array(
            "eid" => $row['ELEMENT_ID'],
            "pid" => $row['PARAM_ID'],
            "cond" => $row['PARAM_CONDITION'],
            "val" => $row['VALUE'],
            "type" => $row['TYPE'],
            "txt" => $text
        );
        array_push($array_exceptions, $exception);
    }
?>
<?php // Elementos p con el texto en formato JSON, para que el JS lo parsee
?>
<p id="params_exceptions" class="d-none"><?= json_encode($array_exceptions); ?></p>

<?php // Textos excepciones convencionales
?>
<p id="conv_exception_texts" class="d-none"><?= json_encode(array_slice($array_texts, 0, 3)); ?></p>

<?php
    // Obtener los valores por defecto de los parámetros de los elementos de configuración y abrir/cerrar
    $default_config_query = mysqli_query($con, "SELECT PARAM_TYPE, DEFAULT_PARAM, ELEMENT_ID FROM parameters WHERE (ELEMENT_ID = 46 or ELEMENT_ID = 8 or ELEMENT_ID = 48 or ELEMENT_ID = 10) and ACTIVE = 1 ORDER BY ELEMENT_ID, ORDER_ID;");
    $default_config_params = array(
        array('element_id' => 46, 'param_type' => [], 'param_value' => []),
        array('element_id' => 8, 'param_type' => [], 'param_value' => []),
        array('element_id' => 48, 'param_type' => [], 'param_value' => []),
        array('element_id' => 10, 'param_type' => [], 'param_value' => [])
    );

    while ($row = mysqli_fetch_array($default_config_query)) {
        $pos = 0;
        if ($row['ELEMENT_ID'] == 46) {
            $pos = 0;
        } else if ($row['ELEMENT_ID'] == 8) {
            $pos = 1;
        } else if ($row['ELEMENT_ID'] == 48) {
            $pos = 2;
        } else {
            // element_id = 10
            $pos = 3;
        }
        array_push($default_config_params[$pos]['param_type'], $row['PARAM_TYPE']);
        array_push($default_config_params[$pos]['param_value'], $row['DEFAULT_PARAM']);
    }
?>
<div id="default_config_params" style="display: none;"><?= json_encode($default_config_params); ?></div>
<?php
    if ($loadStrategy) :
        $lastStrategy = getLastStrategy($con, $user_id);
        $rows_open = array();
        $rows_close = array();
        fullFill_Scenario(0, $lastStrategy['open_scenario']);
        fullFill_Scenario(2, $lastStrategy['close_scenario']);
        $my_strategy = json_encode([
            'rows_open' => $rows_open,
            'rows_close' => $rows_close
        ]);
?>

    <div id="my-strategy" style="display: none;"><?= $my_strategy; ?></div>
<?php endif; ?>

<?php
    $ticker_year_query = mysqli_query($con, "SELECT * FROM ticker_year");
    $tickerYear = array();

    while ($row = mysqli_fetch_assoc($ticker_year_query)) {

        array_push($tickerYear, $row);
    }

?>

<div id="ticker-year-data" class="d-none"><?= json_encode($tickerYear); ?></div>



<input type="hidden" class="validate_visisted">

<input type="hidden" class="element_data_old">

<input type="hidden" class="element_data_new">

<!-- <input type="hidden" class="file_url_compiled"> -->

<a href="" id="myanchor" style="display: none;" target='_blank'></a>







<div class="tooltip_templates">

    <span id="tooltip_content_definition" class="append_response">

    </span>

</div>

<?php

    // popup modal database queries start

    // popuo modal title

    $popup_title = mysqli_query($con, "SELECT Text FROM `translations` WHERE TABLE_NAME='(STRATEGY_SCREEN)' and CONCEPT_NAME='TRY_PREMIUM_TITLE' and LANG_ID='$lang_id'");

    $popup_title = mysqli_fetch_assoc($popup_title)['Text'];



    // popup modal descriotion

    $popup_description = mysqli_query($con, "SELECT Text FROM `translations` WHERE TABLE_NAME='(STRATEGY_SCREEN)' and CONCEPT_NAME='TRY_PREMIUM_DESC' and LANG_ID='$lang_id'");

    $popup_description = mysqli_fetch_assoc($popup_description)['Text'];



    // popup modal button

    $popup_btn = mysqli_query($con, "SELECT Text FROM `translations` WHERE TABLE_NAME='(STRATEGY_SCREEN)' and CONCEPT_NAME='TRY_PREMIUM_BTN' and LANG_ID='$lang_id'");

    $popup_btn = mysqli_fetch_assoc($popup_btn)['Text'];

    // popup modal database queries end



?>



<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel"><?php echo $popup_title; ?></h4>
            </div>
            <div class="modal-body">
                <p><?php echo $popup_description; ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-default"><?php echo $popup_btn; ?></button>
            </div>
        </div>
    </div>
</div>
<!-- <div class="tooltip_templates"></div> -->

<!-- Graph -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/canvasjs/1.7.0/canvasjs.js"></script>
<script class=".to_footer" src="js/JQuery.js?time=<?= time(); ?>"></script>
<script class=".to_footer" src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js?time=<?= time(); ?>" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous">
</script>
<script class=".to_footer" src="lib/bootstrap/js/bootstrap.min.js?time=<?= time(); ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/js/bootstrap-datepicker.js"></script>





<?php if ($lang_id == 'ES') : ?>

    <script src="js/bootstrap-datepicker.es.js" charset="UTF-8"></script>

    <script type="text/javascript">
        $(function() {

            var date_format = $('.datepicker-starttime').attr('data-format');

            $('.datepicker-starttime, .datepicker-endtime').datepicker({

                language: 'es',

                format: date_format,

                orientation: 'top',

                maxViewMode: 2,

                autoclose: true

            });

        });
    </script>

<?php else : ?>

    <script type="text/javascript">
        $(function() {

            var date_format = $(".datepicker-starttime").attr('data-format');

            $('.datepicker-starttime, .datepicker-endtime').datepicker({

                format: date_format,

                orientation: 'top',

                maxViewMode: 2,

                autoclose: true

            });

        });
    </script>

<?php endif; ?>

<script>
    jQuery(document).ready(function() {

        $(".disabled_option").draggable({
            disabled: true
        });

        $(".download_button").click(function() {

            $('#myModal').modal("show");
        });

        $(".freemium_class").click(function() {

            $('#myModal').modal("show");
        });

        $(".disabled_option").click(function() {

            $('#myModal').modal("show");
        });

        $("#timeframe").change(function() {
            var opval = $(this).val();
            if (opval == "disabled") {
                $('#myModal').modal("show");
            }
        });

        $("#ticket").change(function() {
            var opval = $(this).val();
            if (opval == "disabled") {
                $('#myModal').modal("show");
            }
        });

        $("#validation_range").change(function() {
            var opval = $(this).val();
            if (opval == "disabled") {
                $('#myModal').modal("show");
            }
        });

        $('.datepicker-starttime, .datepicker-endtime').on("input change", function(event) {
            let date_format = $(this).attr("data-format").toUpperCase();
            let date = moment($(this).val(), date_format, true);
            let min_date = moment($(this).attr('data-date-start-date'), date_format);
            let max_date = moment($(this).attr('data-date-end-date'), date_format).add(1, 'd');
            /* Check if:
                -> Date is after miDate and before maxDate.
                -> Between startDate & endDate are 7 days.
                Else dates shouldn't be valid.
             */
            if (event.type == "input") {
                if (date.isValid() && date.isBetween(min_date, max_date)) {
                    let start_date = moment($(".datepicker-endtime").val(), date_format);
                    let end_date = moment($(".datepicker-starttime").val(), date_format);
                    if (Math.abs(date.diff(start_date, "days")) >= 7 || Math.abs(date.diff(end_date, "days")) >= 7) {
                        $(this).removeClass("is-invalid").addClass("is-valid");
                        $(this).trigger("classChange");
                    } else {
                        $(this).removeClass("is-valid").addClass("is-invalid");
                        $(this).trigger("classChange");

                    }
                } else {
                    $(this).removeClass("is-valid").addClass("is-invalid");
                    $(this).trigger("classChange");
                }
            } else {
                if (secondValidator()) {
                    $(this).removeClass("is-invalid").addClass("is-valid");
                    $(this).trigger("classChange");
                } else {
                    $(this).removeClass("is-valid").addClass("is-invalid");
                    $(this).trigger("classChange");
                }
            }

        });

        $(".datepicker-starttime, .datepicker-endtime").on("classChange", function() {
            if ($(".datepicker-starttime").hasClass("is-invalid") || $(".datepicker-endtime").hasClass("is-invalid")) {
                $('button#validate-next').attr('disabled', true)
            } else {
                $('button#validate-next').attr('disabled', false)
            }
        })

        $('.datepicker-starttime').next('i').on('click', function(event) {

            $(".datepicker-starttime").trigger("focus");
        });

        $('.datepicker-endtime').next('i').on('click', function(event) {

            $(".datepicker-endtime").trigger("focus");
        });


        /*$(function() {
            // Validador por rango de año
            var selectElem = document.getElementById('validation_range');
            var index = selectElem.selectedIndex;
            // alert(index);
            var options_sel_idx = index;

            $("#validation_range").on("change", this, function(event) {
                if ($(this.options[this.selectedIndex]).hasClass("disabled_option")) {
                    this.selectedIndex = options_sel_idx;
                } else {
                    options_sel_idx = this.selectedIndex;
                }
            });
        });*/

        $(function() {
            var selectElem = document.getElementById('ticket');
            var index = selectElem.selectedIndex;
            // alert(index);
            var options_sel_idx = index;

            $("#ticket").on("change", this, function(event) {
                if ($(this.options[this.selectedIndex]).hasClass("disabled_option")) {
                    this.selectedIndex = options_sel_idx;
                } else {
                    options_sel_idx = this.selectedIndex;
                }
            });
        });

        $(function() {
            var selectElem = document.getElementById('timeframe');
            var index = selectElem.selectedIndex;
            // alert(index);
            var options_sel_idx = index;

            $("#timeframe").on("change", this, function(event) {
                if ($(this.options[this.selectedIndex]).hasClass("disabled_option")) {
                    var selected = this.selectedIndex = options_sel_idx;
                    console.log(selected);
                } else {
                    var selected = options_sel_idx = this.selectedIndex;
                    // console.log(unselected);
                }
            });
        });


        function validateDates(separator) {
            var parents = $('form.validate-form > div.form-group.option-2 div.calendar');
            var inputStart = parents.find('> input[name="start-date"]');
            var inputEnd = parents.find('> input[name="end-date"]');
            var start = inputStart.val();
            var end = inputEnd.val();

            if (start != null && start != "" && end != null && end != "") {
                var validStart = validateDate(inputStart, separator);
                var validEnd = validateDate(inputEnd, separator);

                if (!validStart || !validEnd) {
                    return false;
                }
                parents.find('i.md-error').remove();
                parents.removeAttr('style');
                return true;
            }

            if (start == null || start == "") {

                showErrorIcon(inputStart);
            } else {

                validateDate(inputStart, separator);
            }

            if (end == null || end == "") {
                showErrorIcon(inputEnd);
            } else {
                validateDate(inputEnd, separator);
            }
            return false;
        }

        <?php if ($lang_id == 'ES') : ?>

            window.stringToDate = function(strDate, separator) {
                var splitDate = strDate.split(separator);
                return new Date(splitDate[2], splitDate[1] - 1, splitDate[0]);
            }
        <?php else : ?> window.stringToDate = function(strDate, separator) {
                var splitDate = strDate.split(separator);
                return new Date(splitDate[2], splitDate[0] - 1, splitDate[1]);
            }
        <?php endif; ?>

        function validateDate(input, separator) {

            var date = stringToDate(input.val(), separator);

            if (isValidDate(date)) {

                var defStartDate = stringToDate(input.attr('data-date-start-date'), separator);
                var defEndDate = new Date();

                if (date < defStartDate || date > defEndDate) {

                    if (date < defStartDate) {

                        showErrorIcon(input, 'min');
                    }

                    if (date > defEndDate) {

                        showErrorIcon(input, 'max');
                    }

                    return false;
                }

                removeErrorIcon(input);
                return true;
            }
            showErrorIcon(input);
            return false;
        }
        window.secondValidator = function secondValidator() {
            let fechaActual = new Date(2020, 0, 1);
            <?php
            $user_mode = mysqli_query($con, "SELECT * FROM TE_USERS WHERE USER_ID = $user_id");
            $user_mode = mysqli_fetch_assoc($user_mode);
            ?>
            let isPremium = "<?= $user_mode['TYPE_USER'] ?>";
            var separator = "/";
            var parents = $('form.validate-form > div.d-flex > div.form-group.option-2 div.calendar');
            var start = parents.find('> input[name="start-date"]').val();
            var end = parents.find('> input[name="end-date"]').val();
            start = stringToDate(start, separator);
            end = stringToDate(end, separator);
            resta = end.getTime() - start.getTime();
            var limit = $('form.validate-form select#ticket > option:selected').attr('end-date');
            var historical = stringToDate(limit, separator);
            if (start >= end || start > historical) {
                var errors = $('div.form-group.option-2 > div.calendar-container > ul#calendarErrors');
                var message = errors.find('> li:nth-child(1)').text() + "\n";

                if (start >= end) {
                    message += "- " + errors.find('> li:nth-child(2)').text() + "\n";
                }
                if (start > historical) {
                    console.log(start);
                    message += "- " + errors.find('> li:nth-child(3)').text() + " " + limit;
                }
                customAlert("default", message);

                return false;
            } else if (Math.round(resta / (1000 * 60 * 60 * 24)) < 7) {
                var errors = $('div.form-group.option-2 > div.calendar-container > ul#calendarErrors2');
                var message = errors.find('> li:nth-child(1)').text() + "\n";
                customAlert("default", message);
                return false;
            } else if (start < fechaActual && isPremium == "F") {
                var errors = $('div.form-group.option-2 > div.calendar-container > ul#calendarErrors3');
                var message = errors.find('> li:nth-child(1)').text() + "\n";
                customAlert("default", message);
                return false;
            }
            return true;
        }

        function isValidDate(d) {

            return d instanceof Date && !isNaN(d);
        }

        function showErrorIcon(input, limit) {

            var parent = input.parent();
            parent.css({
                'border-color': 'red'
            });

            if (parent.find('> i.md-error').length == 0) {

                input.before($(
                    '<i class="material-icons md-error" style="color:red; position: absolute; left: 2px;">error</i>'
                ));
            }

            var p = input.closest('.calendar-container').find(' > p');
            var text = p.attr('text');
            p.text(text);

            if (limit !== undefined) {

                if (limit == 'max') {
                    var end_date = input.attr('data-date-end-date');
                    p.text(text + ' (max: ' + end_date + ')');
                } else if (limit == 'min') {
                    var start_date = input.attr('data-date-start-date');
                    p.text(text + ' (min: ' + start_date + ')')
                }
            }

            p.removeAttr('style');
        }

        function removeErrorIcon(input) {
            var parent = input.parent();
            parent.removeAttr('style');
            parent.find('> i.md-error').remove();
            var calendar_container = input.closest('.calendar-container');
            var p = calendar_container.find('> p');
            p.css({
                'display': 'none'
            });
        }
    });
</script>
    </body>

    </html>
<?php endif ?>

<?php
/**
 * Función para obtener la información de la última estrategia.
 * @param object $con
 * @param integer $user_id
 * @return string[]
 */
function getLastStrategy($con, $user_id)
{
    $lastStrategy = null;
    $row =  $con->query("SELECT * FROM session_strategy WHERE user_id = $user_id");
    if (mysqli_num_rows($row) > 0) {

        $lastStrategy = mysqli_fetch_assoc($row);
    }
    return $lastStrategy;
}
/**
 * Función para obtener el nombre de la última estrategia.
 * @param object $con
 * @param integer $strategy_id
 * @param string $origin
 * @return string[]
 */
function get_strategy_name($con, $strategy_id, $origin)
{
    $row = "";
    if ($origin === "M") {
        $row =  $con->query("SELECT name FROM my_strategies WHERE strategy_id = $strategy_id");
    } else if ($origin === "S") {
        $row =  $con->query("SELECT title FROM shared_strategies WHERE shared_strategies_id = $strategy_id");
    }
    $strategy_name = mysqli_fetch_assoc($row);
    return $strategy_name;
}
function fullFill_Scenario($actID, $scenario)
{
    global $con;
    global $rows_open;
    global $rows_close;
    $lines = explode("@", $scenario);
    $pos_element = 0;
    $element_iterator = 0;

    foreach ($lines as $line) {

        if ($line != "") {
            $line_elements_open = array();
            $line_elements_close = array();
            $elements = explode(";", $line);
            $sec_element = 0;

            foreach ($elements as $element) {
                if ($element != "") {

                    $params = explode(",", $element);
                    $list_params = $params;
                    $identifier = $list_params[0];
                    array_shift($list_params);

                    $e_temp = new Element_Str($identifier, $element_iterator, receiveName($con, $identifier), $pos_element, $sec_element, $list_params, receiveParamName($con, $identifier), receiveParamType($con, $identifier), array(), receiveElementName($con, $identifier, $lang_id), receiveParamNameDescrp($con, $identifier, $lang_id));

                    if ($actID == 0) {

                        array_push($line_elements_open, $e_temp);
                    } else if ($actID == 1) {
                    } else {

                        array_push($line_elements_close, $e_temp);
                    }

                    $element_iterator++;
                }
            }

            array_push($rows_open, $line_elements_open);

            array_push($rows_close, $line_elements_close);

            $pos_element++;
        }
    }

    if (count($rows_open) > 0) {

        $rows_open = array_values(array_filter($rows_open));
    }

    if (count($rows_close) > 0) {

        $rows_close = array_values(array_filter($rows_close));
    }
}
function receiveName($con, $element_id)
{

    $row = "";
    $plain = "SELECT ELEMENT_NAME FROM elements WHERE ELEMENT_ID = {$element_id} AND ACTIVE = true ORDER BY ORDER_ID ASC";
    $result = $con->query($plain);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        encodes($row['ELEMENT_NAME']);
        //$row = encodes());
    }
    return $row;
}
function receiveParamName($con, $element_id)
{

    $tmp = array();

    $result = $con->query("SELECT PARAM_NAME FROM parameters WHERE ELEMENT_ID = {$element_id} AND ACTIVE = true ORDER BY ORDER_ID ASC");

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            array_push($tmp, encodes($row['PARAM_NAME']));
        }
    }
    return $tmp;
}
function receiveParamType($con, $element_id)
{

    $tmp = array();

    $result = $con->query("SELECT PARAM_TYPE FROM parameters WHERE ELEMENT_ID = {$element_id} AND ACTIVE = true ORDER BY ORDER_ID ASC");

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            array_push($tmp, encodes($row['PARAM_TYPE']));
        }
    }
    return $tmp;
}
function receiveElementName($con, $element_id, $lang_id)
{

    $element_name = "";

    $result = $con->query("SELECT TEXT FROM translations WHERE REG_ID = {$element_id} AND TABLE_NAME = 'ELEMENTS' AND CONCEPT_NAME = 'ELEMENT_NAME' AND LANG_ID = '$lang_id'");
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $element_name = encodes($row['TEXT']);
    }

    return $element_name;
}
function receiveParamNameDescrp($con, $element_id, $lang_id)
{

    $tmp = array();

    $result = $con->query("SELECT t2.text FROM translations t2, elements e, parameters p WHERE e.element_id = p.element_id and t2.table_name = 'PARAMETERS' and t2.concept_name = 'PARAM_NAME' and p.param_id = t2.reg_id and e.active = true and p.active = true and e.element_id = $element_id and t2.lang_id = '$lang_id' ORDER BY p.order_id");

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            array_push($tmp, encodes($row['text']));
        }
    }

    return $tmp;
}
class Element_Str
{

    // Atributos
    public $element_id;
    public $order_id;
    public $call_name;
    public $lineNumber;
    public $secNumber;
    public $param_value;
    public $param_name;
    public $param_type;
    public $param_decl_name;
    public $element_name;
    public $param_nameDescription;



    public function __construct($element_id, $order_id, $call_name, $lineNumber, $secNumber, $param_value, $param_name, $param_type, $param_decl_name, $element_name, $param_nameDescription)
    {

        $this->element_id = $element_id;
        $this->order_id = $order_id;
        $this->call_name = $call_name;
        $this->lineNumber = $lineNumber;
        $this->secNumber = $secNumber;
        $this->param_value = $param_value;
        $this->param_name = $param_name;
        $this->param_type = $param_type;
        $this->param_decl_name = $param_decl_name;
        $this->element_name = $element_name;
        $this->param_nameDescription = $param_nameDescription;
    }
}
// FIN - Marc - 03/12/19 - Funciones para obtener la última estrategia, para que después el JS la muestre
?>
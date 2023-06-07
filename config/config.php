<?php
//MySQL config
//Preencha com os seus dados
$servername = ""; 
$username = "";
$password = "";
$dbname = "";

function test_input($data) {
	if(!empty($data)){
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $data;
	}
}

function checkPISPASEP($strPISPASEP) {
    $strPeso = "3298765432";
    $intTotal = 0;

    $strPISPASEP = str_replace(array(".", "-"), "", $strPISPASEP);
    
    if ($strPISPASEP == "" || strlen($strPISPASEP) != 11) return false;
    
    for ($intCont = 0; $intCont < 10; $intCont++) {
        $intResultado = substr($strPISPASEP, $intCont, 1) * substr($strPeso, $intCont, 1);
        $intTotal += $intResultado;
    }

    $intResto = $intTotal % 11;
    if ($intResto != 0) $intResto = 11 - $intResto;    
    if ($intResto == 10 || $intResto == 11) $intResto = substr($intResto, 1, 1);
    if (intval($intResto) != intval(substr($strPISPASEP, 10, 1))) return false;    
    
    return true;
}

?>
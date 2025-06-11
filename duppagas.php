<?php
	if (!$nocache) ini_set("session.cache_limiter", "private");

    session_start();
	
	if ($dispositivomovelnovo) {
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">

<?php
		include_once("dispositivomovelhead.php");
	} else {
?>

<html>

<?php
	}
?>

<head>
<!-- 
Autoria: Lauro César A. Alves
Goiânia-GO, Março de 2006
-->
<title>Duplicatas Pagas</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?php
if ($grupo <> '' && $usuariologado == $usuario && (($acesso_fin_receitas != 'N' && $acesso_fin_receitas != '') || ($acesso_fin_despesas != 'N' && $acesso_fin_despesas != ''))) {
	include_once("bdoraclemge2.php");
	include_once("classes/phpmailer/class.phpmailer.php");
	
	$data1 = trim($data1);
	$data2 = trim($data2);
	$data3 = trim($data3);
	$data4 = trim($data4);
	$dataneg1 = trim($dataneg1);
	$dataneg2 = trim($dataneg2);
	$dataentsai1 = trim($dataentsai1);
	$dataentsai2 = trim($dataentsai2);
	
	if (!$btnbuscar && $data1 == '' && $data2 == '' && $dataneg1 == '' && $dataneg2 == '' && $dataentsai1 == '' && $dataentsai2 == '') {
		$data1 = date("d/m/Y");
		$data2 = date("d/m/Y");
	}
	
	// Retirado devido travas supervisores.
	if ($grupo != 'Dir' && $grupo != 'Inf' && $grupo != 'Pes' && $grupo != 'DirBV' && $grupo != 'Fin' && $grupo != 'GerFin' && $grupo != 'Ctb' && $grupo != 'Rec' && $grupo != 'Sec' && $grupo != 'Ger' && $grupo != 'Ope' && $grupo != 'GerInd' && $cp_codusu == '') {
		$cp_codusu = $codusuario;
	} else {
		// Se Grupo Faturamento ver clientes.
		if ($grupo == 'Fat') $verapenasclientes = 'S';
	}
	
	// Verificando se é Supervisor
	if ($grupo == 'Dir' || $grupo == 'Inf' || $grupo == 'Pes' || $grupo == 'DirBV' || $grupo == 'Fin' || $grupo == 'GerFin' || $grupo == 'Ctb' || $grupo == 'Rec' || $grupo == 'Sec' || $grupo == 'Ger' || $grupo == 'Ope' || $grupo == 'GerInd') {
		// Os grupos acima sempre veem tudo.
		$temequipe = false;
	} else {
		$temequipe = true;
	}
	
	// Lauro/Gleidson - 09/05/15 - Permitir Usuário ou CR
	if ($btnbuscar && $temequipe && $cp_codusu == '' && $cb_codcencus == '' && $grupo != 'GerInd') {
		$mensagem_trava = "Atenção! Informe o USUÁRIO ou o CENTRO DE RESULTADO.";
		echo "<script>window.location = 'mensagem_trava.php?PHPSESSID=$PHPSESSID&mensagem_trava=$mensagem_trava';</script>";
		exit;
	}
	
	if ($codparc != '') {
		// Parceiro
		$sql1 = "SELECT P.NOMEPARC FROM TGFPAR P WHERE P.CODPARC = $codparc";
		$bdsql1 = new BDORACLE();
		$bdsql1->desconecta();
		$bdsql1->conecta($usubanco);
		$bdsql1->execute_query($sql1,$bdsql1->conexao);
		while ($dt1 = $bdsql1->result_query()) {
	    	$nomeparc = $dt1['NOMEPARC'];
		}
	}
	
	// Lista adicional Usuario
	if ($grupo == 'Qua') $listaadicionalusu = '393,836,879,1097,1098';
	else $listaadicionalusu = $codusuario;
	
	// BUSCANDO USUARIOS
	$sqlUsuario = "SELECT DISTINCT(U.CODUSU),U.NOMEUSU
				   FROM TSIUSU U, TSIUSU U1
				   WHERE U.CODGRUPO <> 20 
				   AND U1.CODUSU(+) = U.AD_CODUSUSUP ";
	if ($temequipe) $sqlUsuario .= " AND (U.AD_CODUSUSUP = '$codusuario' OR U.CODUSU = '$codusuario' OR U1.AD_CODUSUSUP = '$codusuario' OR U.CODUSU IN ($listaadicionalusu)) ";
	$sqlUsuario .= " ORDER BY U.NOMEUSU";
	$bdsqlUsuario = new BDORACLE();
	$bdsqlUsuario->desconecta();
	$bdsqlUsuario->conecta($usubanco);
	$bdsqlUsuario->execute_query($sqlUsuario,$bdsqlUsuario->conexao);
	
	// BUSCANDO PROJETO
	$sqlProjeto = "SELECT R.CODPROJ, R.IDENTIFICACAO FROM TCSPRJ R ORDER BY R.IDENTIFICACAO";
	$bdsqlProjeto = new BDORACLE();
	$bdsqlProjeto->desconecta();
	$bdsqlProjeto->conecta($usubanco);
	$bdsqlProjeto->execute_query($sqlProjeto,$bdsqlProjeto->conexao);
	
	// Natureza
	$sqlNat = "SELECT CODNAT, DESCRNAT, INCRESULT, AD_CONSUMO, AD_UNICA, AD_FOLHA, AD_SEMMETA
			   FROM TGFNAT
			   WHERE CODNAT <> 0 AND ATIVA = 'S' AND ANALITICA = 'S' ";
	if ($ck_grupo == 'GerInd') $sqlNat .= " AND CODNAT IN (1050600,8010200)";
	$sqlNat .= " ORDER BY DESCRNAT";
	$bdsqlNat = new BDORACLE();
	$bdsqlNat->desconecta();
	$bdsqlNat->conecta($usubanco);
	$bdsqlNat->execute_query($sqlNat,$bdsqlNat->conexao);
	
	// CENTRO DE RESULTADO
	$sqlCus = "SELECT C.CODCENCUS, C.DESCRCENCUS, C.ATIVO, U.NOMEUSU, UBV.NOMEUSU AS NOMEUSUBV
			   FROM TSICUS C, TSIUSU U, TSIUSU UBV
			   WHERE C.CODCENCUS <> 0 AND ATIVO = 'S' AND C.ANALITICO = 'S' AND U.CODUSU(+) = C.CODUSURESP AND UBV.CODUSU(+) = C.AD_CODUSURESP ";
	if ($grupo != 'Dir' && $grupo != 'Inf' && $grupo != 'Fin' && $grupo != 'GerFin' && $grupo != 'Ctb' && $grupo != 'Sec' && $usuario != 'RAIANE' && $grupo <> 'Rec') {
		$sqlCus .= " AND 
					(
					 C.CODCENCUS IN (SELECT C1.CODCENCUS FROM TSICUS C1 WHERE C1.CODUSURESP = $codusuario AND C1.ANALITICO = 'S') OR
					 C.CODCENCUSPAI IN (SELECT C1.CODCENCUS FROM TSICUS C1 WHERE C1.CODUSURESP = $codusuario AND C1.ANALITICO = 'N') OR 
					 C.CODCENCUSPAI IN (SELECT C1.CODCENCUS FROM TSICUS C1 WHERE C1.ANALITICO = 'N' AND C1.CODCENCUSPAI IN (SELECT C2.CODCENCUS FROM TSICUS C2 WHERE C2.CODUSURESP = $codusuario AND C2.ANALITICO = 'N')) OR
					 
					 C.CODCENCUS IN (SELECT C1.CODCENCUS FROM TSICUS C1 WHERE C1.AD_CODUSURESP = $codusuario AND C1.ANALITICO = 'S') OR
					 C.CODCENCUSPAI IN (SELECT C1.CODCENCUS FROM TSICUS C1 WHERE C1.AD_CODUSURESP = $codusuario AND C1.ANALITICO = 'N') OR 
					 C.CODCENCUSPAI IN (SELECT C1.CODCENCUS FROM TSICUS C1 WHERE C1.ANALITICO = 'N' AND C1.CODCENCUSPAI IN (SELECT C2.CODCENCUS FROM TSICUS C2 WHERE C2.AD_CODUSURESP = $codusuario AND C2.ANALITICO = 'N'))
					) ";
	}
	$sqlCus .= " ORDER BY C.DESCRCENCUS";
	$bdsqlCus = new BDORACLE();
	$bdsqlCus->desconecta();
	$bdsqlCus->conecta($usubanco);
	$bdsqlCus->execute_query($sqlCus,$bdsqlCus->conexao);
	
	// Situação
	$sqlSit = "SELECT CODSITUACAO,DESCRSITUACAO FROM NATIVA_SIT ORDER BY DESCRSITUACAO";
	$bdsqlSit = new BDORACLE();
	$bdsqlSit->desconecta();
	$bdsqlSit->conecta($usubanco);
	$bdsqlSit->execute_query($sqlSit,$bdsqlSit->conexao);
	
	// BUSCANDO TIPOS DE TITULO
	$sqlTitulo = "SELECT T.CODTIPTIT,T.DESCRTIPTIT FROM TGFTIT T ORDER BY T.CODTIPTIT,T.DESCRTIPTIT";
	$bdsqlTitulo = new BDORACLE();
	$bdsqlTitulo->desconecta();
	$bdsqlTitulo->conecta($usubanco);
	$bdsqlTitulo->execute_query($sqlTitulo,$bdsqlTitulo->conexao);
	
	if ($btnbuscar) {
		$cont = 0;
		$vetor = '';
		
		// Buscar Lançamentos pagos
		if ($cb_agruparmatriz) {
			$sql = "SELECT F.NUFIN, F.CODEMP, P.CODPARCMATRIZ AS CODPARC, F.NUNOTA, F.NUMNOTA, F.DTNEG, F.DTVENC, F.DHBAIXA,
					F.VLRBAIXA, MAX(P.NOMEPARC) AS NOMEPARC, F.HISTORICO, F.CODNAT,
					F.AD_CONFERIDO, F.ORIGEM, U3.NOMEUSU AS USUARIO_FIN, F.CODUSU, F.AD_NUMPEDIDO,
					F.CODPROJ, F.VLRDESDOB, F.VLRDESC, F.DESPCART, F.VLRMULTA, F.VLRJURO, F.AD_CONF_CONTAB, F.DESDOBRAMENTO, 
					N.INCRESULT, N.AD_CONSUMO, N.AD_UNICA, N.AD_FOLHA, N.AD_SEMMETA,
					(SELECT U4.CODUSU FROM TGFCAB C4, TSIUSU U4
							WHERE C4.NUNOTA = F.NUNOTA AND U4.CODUSU = C4.CODUSU) AS CODUSU_CAB,
					(SELECT U4.NOMEUSU FROM TGFCAB C4, TSIUSU U4
							WHERE C4.NUNOTA = F.NUNOTA AND U4.CODUSU = C4.CODUSU) AS USUARIO_CAB
					FROM TGFFIN F, TGFPAR P, TSIUSU U3, TGFNAT N ";
		} else {
			$sql = "SELECT F.NUFIN, F.CODEMP, F.CODPARC, F.NUNOTA, F.NUMNOTA, F.DTNEG, F.DTVENC, F.DHBAIXA,
					F.VLRBAIXA, P.NOMEPARC, F.HISTORICO, F.CODNAT,
					F.AD_CONFERIDO, F.ORIGEM, U3.NOMEUSU AS USUARIO_FIN, F.CODUSU, F.AD_NUMPEDIDO,
					F.CODPROJ, F.VLRDESDOB, F.VLRDESC, F.VLRISS, F.DESPCART, F.VLRMULTA, F.VLRJURO, F.AD_CONF_CONTAB, F.DESDOBRAMENTO, 
					N.INCRESULT, N.AD_CONSUMO, N.AD_UNICA, N.AD_FOLHA, N.AD_SEMMETA,
					(SELECT U4.CODUSU FROM TGFCAB C4, TSIUSU U4
							WHERE C4.NUNOTA = F.NUNOTA AND U4.CODUSU = C4.CODUSU) AS CODUSU_CAB,
					(SELECT U4.NOMEUSU FROM TGFCAB C4, TSIUSU U4
							WHERE C4.NUNOTA = F.NUNOTA AND U4.CODUSU = C4.CODUSU) AS USUARIO_CAB
					FROM TGFFIN F, TGFPAR P, TSIUSU U3, TGFNAT N ";
		}
		
		if ($cb_compensado != '') $sql .= ", TGFFRE FRE ";
		
		$sql .= " WHERE F.RECDESP = -1 AND F.DHBAIXA IS NOT NULL AND P.CODPARC = F.CODPARC
				  AND F.PROVISAO = 'N' AND U3.CODUSU(+) = F.CODUSU AND N.CODNAT = F.CODNAT ";
		
		if ($codparc != '' && !$cb_agruparmatriz) {
			$sql .= " AND F.CODPARC = $codparc";
			
			// Parceiro
			$sql1 = "SELECT P.NOMEPARC FROM TGFPAR P WHERE P.CODPARC = $codparc";
			$bdsql1 = new BDORACLE();
			$bdsql1->desconecta();
			$bdsql1->conecta($usubanco);
			$bdsql1->execute_query($sql1,$bdsql1->conexao);
			while ($dt1 = $bdsql1->result_query()) {
				$nomeparc = $dt1['NOMEPARC'];
			}
		}
		if ($codparc != '' && $cb_agruparmatriz) $sql .= " AND (P.CODPARCMATRIZ = $codparc OR P.CODPARC = $codparc) ";
		if ($cp_nomeparc != '')					 $sql .= " AND UPPER(P.NOMEPARC) LIKE UPPER('%$cp_nomeparc%') ";
		
		if ($codemp != '')  		$sql .= " AND F.CODEMP = $codemp";
		if ($numnota != '') 		$sql .= " AND F.NUMNOTA = $numnota";
		if ($empresas != '')  		$sql .= " AND F.CODEMP IN ($empresas)";
		if ($cp_origem != '') 		$sql .= " AND F.ORIGEM = '$cp_origem'";
		if ($cb_codnat != '') 	  	$sql .= " AND F.CODNAT = $cb_codnat";
		if ($cb_codcencus != '') 	$sql .= " AND F.CODCENCUS = $cb_codcencus";
		if ($codproj != '') 		$sql .= " AND F.CODPROJ = $codproj";
		if ($sl_codtiptit != '')   	$sql .= " AND F.CODTIPTIT = '$sl_codtiptit'";
		if ($cb_codsituacao != '') 	$sql .= " AND F.AD_CODSIT = '$cb_codsituacao'";
		if ($cb_comjuros != '') 	$sql .= " AND (F.VLRJURO > 0 OR F.VLRMULTA > 0 OR F.VLRBAIXA - F.VLRDESDOB > 0)";
		if ($cb_comdesc)  			$sql .= " AND (F.VLRDESC > 0 OR F.VLRBAIXA - F.VLRDESDOB < 0)";
		if ($cb_conferido != '')   	$sql .= " AND UPPER(F.AD_CONFERIDO) = '$cb_conferido'";
		
		if ($cb_conf_contab <> '')  {
			if ($cb_conf_contab == 'S') $sql .= " AND SUBSTR(UPPER(F.AD_CONF_CONTAB),1,1) = 'S'";
			else $sql .= " AND (SUBSTR(UPPER(F.AD_CONF_CONTAB),1,1) = 'N' OR F.AD_CONF_CONTAB IS NULL)";
		}
		
		if ($data1 != '' && $data2 != '') $sql .= " AND TRUNC(F.DHBAIXA) BETWEEN '$data1' AND '$data2'";
		if ($data3 != '' && $data4 != '') $sql .= " AND F.DTVENC BETWEEN '$data3' AND '$data4'";
		if ($dataneg1 != '' && $dataneg2 != '') $sql .= " AND F.DTNEG BETWEEN '$dataneg1' AND '$dataneg2'";
		if ($dataentsai1 != '' && $dataentsai1 != '') $sql .= " AND DECODE(F.DTENTSAI,'',F.DTNEG,F.DTENTSAI) BETWEEN '$dataentsai1' AND '$dataentsai2'";
		
		if ($cb_natvalidada <> '' || $cb_natconsumo <> '' || $cb_natunica <> '' || $cb_natfolha <> '' || $cb_natsemmeta <> '' || $cb_natoutras <> '') {
			$sql .= " AND ( ";
			if ($cb_natvalidada <> '') 	$sql .= " N.INCRESULT = '$cb_natvalidada'";
			else 						$sql .= " N.INCRESULT = 'VALIDA OR'";
			if ($cb_natconsumo <> '') 	$sql .= " OR N.AD_CONSUMO = '$cb_natconsumo'";
			if ($cb_natunica <> '') 	$sql .= " OR N.AD_UNICA = '$cb_natunica'";
			if ($cb_natfolha <> '') 	$sql .= " OR N.AD_FOLHA = '$cb_natfolha'";
			if ($cb_natsemmeta <> '') 	$sql .= " OR N.AD_SEMMETA = '$cb_natsemmeta'";
			if ($cb_natoutras <> '') 	$sql .= " OR (N.INCRESULT = 'N' AND N.AD_CONSUMO = 'N' AND N.AD_UNICA = 'N' AND N.AD_FOLHA = 'N' AND N.AD_SEMMETA = 'N')";
			$sql .= " ) ";
		}
		
		if ($cb_compensado != '') {
            $sql .= " AND FRE.NUFIN(+) = F.NUFIN AND FRE.TIPACERTO(+) = 'C'
					  AND (F.NUCOMPENS IS NULL OR FRE.NUACERTO IS NULL) ";
        }
		
		if ($cb_agruparmatriz) {
			$sql .= " GROUP BY 	F.NUFIN, F.CODEMP, P.CODPARCMATRIZ,
								F.NUNOTA, F.NUMNOTA, F.CODNAT,
								F.DTNEG, F.DTVENC, F.DHBAIXA, 
								F.VLRBAIXA, F.HISTORICO, N.INCRESULT, N.AD_CONSUMO, N.AD_UNICA, N.AD_FOLHA, N.AD_SEMMETA, 
								F.AD_CONFERIDO, F.ORIGEM, U3.NOMEUSU, F.CODUSU, F.AD_NUMPEDIDO, F.CODPROJ, F.VLRDESDOB, 
								F.VLRDESC, F.VLRISS, F.DESPCART, F.VLRMULTA, F.VLRJURO, F.AD_CONF_CONTAB, F.DESDOBRAMENTO
					  ORDER BY P.CODPARCMATRIZ, F.CODEMP, F.NUMNOTA";
		} else {
			$sql .= " ORDER BY P.NOMEPARC, F.DHBAIXA, F.NUMNOTA";
		}
//if ($usuario == 'LAURO') echo $sql;		
		$bdsql = new BDORACLE();
		$bdsql->desconecta();
		$bdsql->conecta($usubanco);
		$bdsql->execute_query($sql,$bdsql->conexao);
		while ($dt = $bdsql->result_query()) {		
			$cont++;
			
			if ($cb_agruparmatriz) {
				$vetor[$cont]['codparcmatriz'] = $dt['CODPARCMATRIZ'];
				$vetor[$cont]['codemp'] = $dt['CODEMP'];
				$vetor[$cont]['numnota'] = $dt['NUMNOTA'];
			} else {
				$vetor[$cont]['nomeparc'] = $dt['NOMEPARC'];
				$vetor[$cont]['dhbaixa'] = $dt['DHBAIXA'];
				$vetor[$cont]['numnota'] = $dt['NUMNOTA'];
			}
			
			$vetor[$cont]['parceiro'] = $dt['CODPARC'];
			$vetor[$cont]['nomeparc'] = $dt['NOMEPARC'];		
			$vetor[$cont]['nufin'] = $dt['NUFIN'];
			$vetor[$cont]['codemp'] = $dt['CODEMP'];
			$vetor[$cont]['nunota'] = $dt['NUNOTA'];
			$vetor[$cont]['dtneg'] = $dt['DTNEG'];
			$vetor[$cont]['dtvenc'] = $dt['DTVENC'];
			$vetor[$cont]['dhbaixa'] = $dt['DHBAIXA'];
			$vetor[$cont]['historico'] = $dt['HISTORICO'];
			$vetor[$cont]['nomeusuario_fin'] = $dt['USUARIO_FIN'];
			$vetor[$cont]['nomeusuario_cab'] = $dt['USUARIO_CAB'];
			$vetor[$cont]['codusuario_cab'] = $dt['CODUSU_CAB'];
			$vetor[$cont]['codusufin'] = $dt['CODUSU'];
			$vetor[$cont]['origem'] = $dt['ORIGEM'];
			$vetor[$cont]['ad_conferido'] = $dt['AD_CONFERIDO'];
			$vetor[$cont]['ad_conf_contab'] = $dt['AD_CONF_CONTAB'];
			$vetor[$cont]['ad_numpedido'] = $dt['AD_NUMPEDIDO'];
			$vetor[$cont]['vlrbaixa'] = $dt['VLRBAIXA'];
			$vetor[$cont]['vlrdesdob'] = $dt['VLRDESDOB'];
			$vetor[$cont]['vlrdesc'] = $dt['VLRDESC'];
			$vetor[$cont]['vlriss'] = $dt['VLRISS'];
			$vetor[$cont]['descrcencus'] = $dt['DESCRCENCUS'];
			$vetor[$cont]['desdobramento'] = $dt['DESDOBRAMENTO'];
			
			$vetor[$cont]['vlracres'] = str_replace('.',',',(str_replace(',','.',$dt['DESPCART'])+str_replace(',','.',$dt['VLRMULTA'])+str_replace(',','.',$dt['VLRJURO'])));
			
			$dt['HISTORICO'] = $dt['DESDOBRAMENTO'] = '';
			$dt['USUARIO_FIN'] = $dt['USUARIO_CAB'] = '';
			$dt['AD_CONFERIDO'] = $dt['AD_CONF_CONTAB'] = $dt['VLRISS'] = '';
			$dt['AD_AUTORIZADO'] = $dt['AUTORIZADO'] = $dt['AD_NUMPEDIDO'] = '';
			$dt['ORIGEM'] = $dt['NUNOTA'] = $dt['DESPCART'] = $dt['VLRMULTA'] = $dt['VLRJURO'] = '';
		}
		
		// Buscar base EX
		if ($ck_baseEX && $_SESSION["nomeservidor"] != 'SRVEX' && ($codemp == '' || $codemp > 500)) {
			$bdsql = new BDORACLE();
			$bdsql->desconecta();
			$bdsql->conectaEX($usubanco);
			$bdsql->execute_query($sql,$bdsql->conexao);
			while ($dt = $bdsql->result_query()) {
				$cont++;
				
				if ($cb_agruparmatriz) {
					$vetor[$cont]['codparcmatriz'] = $dt['CODPARCMATRIZ'];
					$vetor[$cont]['codemp'] = $dt['CODEMP'];
					$vetor[$cont]['numnota'] = $dt['NUMNOTA'];
				} else {
					$vetor[$cont]['nomeparc'] = $dt['NOMEPARC'];
					$vetor[$cont]['dhbaixa'] = $dt['DHBAIXA'];
					$vetor[$cont]['numnota'] = $dt['NUMNOTA'];
				}
				
				$vetor[$cont]['parceiro'] = $dt['CODPARC'];
				$vetor[$cont]['nomeparc'] = $dt['NOMEPARC'];		
				$vetor[$cont]['nufin'] = $dt['NUFIN'];
				$vetor[$cont]['codemp'] = $dt['CODEMP'];
				$vetor[$cont]['nunota'] = $dt['NUNOTA'];
				$vetor[$cont]['dtneg'] = $dt['DTNEG'];
				$vetor[$cont]['dtvenc'] = $dt['DTVENC'];
				$vetor[$cont]['dhbaixa'] = $dt['DHBAIXA'];
				$vetor[$cont]['historico'] = $dt['HISTORICO'];
				$vetor[$cont]['nomeusuario_fin'] = $dt['USUARIO_FIN'];
				$vetor[$cont]['nomeusuario_cab'] = $dt['USUARIO_CAB'];
				$vetor[$cont]['codusuario_cab'] = $dt['CODUSU_CAB'];
				$vetor[$cont]['codusufin'] = $dt['CODUSU'];
				$vetor[$cont]['origem'] = $dt['ORIGEM'];
				$vetor[$cont]['ad_conferido'] = $dt['AD_CONFERIDO'];
				$vetor[$cont]['ad_conf_contab'] = $dt['AD_CONF_CONTAB'];
				$vetor[$cont]['ad_numpedido'] = $dt['AD_NUMPEDIDO'];
				$vetor[$cont]['vlrbaixa'] = $dt['VLRBAIXA'];
				$vetor[$cont]['vlrdesdob'] = $dt['VLRDESDOB'];
				$vetor[$cont]['vlrdesc'] = $dt['VLRDESC'];
				$vetor[$cont]['vlriss'] = $dt['VLRISS'];
				$vetor[$cont]['descrcencus'] = $dt['DESCRCENCUS'];
				$vetor[$cont]['desdobramento'] = $dt['DESDOBRAMENTO'];
				
				$vetor[$cont]['vlracres'] = str_replace('.',',',(str_replace(',','.',$dt['DESPCART'])+str_replace(',','.',$dt['VLRMULTA'])+str_replace(',','.',$dt['VLRJURO'])));
				
				$dt['HISTORICO'] = $dt['DESDOBRAMENTO'] = '';
				$dt['USUARIO_FIN'] = $dt['USUARIO_CAB'] = '';
				$dt['AD_CONFERIDO'] = $dt['AD_CONF_CONTAB'] = $dt['VLRISS'] = '';
				$dt['AD_AUTORIZADO'] = $dt['AUTORIZADO'] = $dt['AD_NUMPEDIDO'] = '';
				$dt['ORIGEM'] = $dt['NUNOTA'] = $dt['DESPCART'] = $dt['VLRMULTA'] = $dt['VLRJURO'] = '';
			}
		}		
		
		// Ordenando o array
		if ($vetor != '') sort($vetor);
	}
?>
<style>
<!--
body {background-color:white}
A {text-decoration: none; color:#003366}
a:hover {color:red;}
-->
</style>
</head>

<body bgcolor="#FFFFFF" text="#000000" onLoad="form1.data1.focus()" leftmargin="0" topmargin="0">
<?php 
	$paginaorigem = end(explode("/", $_SERVER['PHP_SELF']));
	include_once("menu.php");
?>
<br>
<table border="0" cellspacing="1" cellpadding="0" align="center" bordercolor="#FFFFFF" bgcolor="#CCCCCC" width="1200">
  <tr>
	<td height="20" width="1%"><a href="duppagas.php?PHPSESSID=<?php echo $PHPSESSID;?>&nocache=true"><img src="img/fundotitulovoltar.jpg" border="0" alt="Voltar a Tela"></a></td>
	<td height="20" bgcolor="#E5EEEE" bordercolor="#E5EEEE" width="97%">
		<div align="center"> <font size="2" face="Arial" color="#000000"><b>Duplicatas Pagas</b></font></div>
	</td>
	<td height="20" width="1%" align="right"><img src="img/ajuda.jpg" border="0" alt="Ajuda" style="cursor: hand" onClick="window.open('ajuda.php?PHPSESSID=<?php echo $PHPSESSID;?>&codajuda=34', 'JANELA', 'left = 50, top = 50, height = 550, width = 770, scrollbars=yes, titlebar=yes, menubar=yes, location=yes, toolbar=no, status=yes, resizable=yes')"></td>
	<td height="20" width="1%"><a href='home.php'><img src="img/fundotitulofechar2.jpg" border="0" alt="Fechar"></a></td>
  </tr>   
  <tr> 
    <td bgcolor="#FFFFFF" bordercolor="#FFFFFF" valign="middle" colspan="4"> <br>
	  <form name="form1" method="post" action="duppagas.php?PHPSESSID=<?php echo $PHPSESSID;?>&btnbuscar=true">
        <table border="1" cellspacing="1" cellpadding="1" align="center" bordercolor="#FFFFFF" bgcolor="#000000" width="700">
          <tr> 
            <td bgcolor="#99AACC" bordercolor="#99AACC" width="130"><b><font face="Arial" size="2"><a href="parceiros.php?PHPSESSID=<?php echo $PHPSESSID;?>&voltar=duppagas.php">Parceiro</a></font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE" width="570" colspan="3"> 
              <input type="text" name="codparc" size="5" maxlength="5" value="<?php echo $codparc;?>">
              &nbsp;<font size="2"><font face="Arial" size="2"> 
              <?php echo substr($nomeparc,0,34);?>
              </font></font>
			</td>
          </tr>
		  <tr>
            <td valign="middle" bgcolor="#99ACCC" bordercolor="#99ACCC" title="Informe o nome do cliente">
              <font face="Arial" size="2"><b>Nome Parceiro</b></font>
            </td>
            <td valign="middle" bgcolor="#EEEEEE" bordercolor="#EEEEEE" colspan="3">
              <input type="text" name="cp_nomeparc" size="50" maxlength="50" value="<?php echo $cp_nomeparc;?>">
            </td>
		  </tr>
          <tr> 
            <td bgcolor="#99AACC" bordercolor="#99AACC" width="130"><b><font face="Arial" size="2">Empresa</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE" width="280"> 
              <input type="text" name="codemp" size="3" maxlength="5" value="<?php echo $codemp;?>">
            </td>
            <td bgcolor="#99AACC" bordercolor="#99AACC" width="140"><b><font face="Arial" size="2">Nota&nbsp;Fiscal</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE" width="150"> 
              <input type="text" name="numnota" size="5" value="<?php echo $numnota;?>">
            </td>
          </tr>
          <tr>
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Data&nbsp;Neg.</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE">
              <input type="text" name="dataneg1" size="10" maxlength="10" value="<?php echo $dataneg1;?>">
              <font face="Arial" size="2">a</font>
              <input type="text" name="dataneg2" size="10" maxlength="10" value="<?php echo $dataneg2;?>">
            </td>
			<td valign="middle" bgcolor="#66CCCC" bordercolor="#66CCCC"><b><font face="Arial" size="2">Não Compensados</font></b></td>
            <td valign="middle" bgcolor="#EEEEEE" bordercolor="#EEEEEE">
			  <input name="cb_compensado" type="checkbox" id="cb_compensado" value="S" <?php if ($cb_compensado) echo 'checked'; ?>>
			</td>
          </tr>
          <tr>
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Data&nbsp;Venc</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE">
              <input type="text" name="data3" size="10" maxlength="10" value="<?php echo $data3;?>">
              <font face="Arial" size="2">a</font>
              <input type="text" name="data4" size="10" maxlength="10" value="<?php echo $data4;?>">
            </td>
			<td bgcolor="#99ACCC" bordercolor="#99ACCC"><b><font face="Arial" size="2">Matriz</font></b></td>
			<td bgcolor="#EEEEEE" bordercolor="#EEEEEE">
			  <input type="checkbox" name="cb_agruparmatriz" value="checkbox" <?php if ($cb_agruparmatriz) echo 'checked';?>>
			</td>	
          </tr>
          <tr> 
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Data&nbsp;Baixa</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE"> 
              <input type="text" name="data1" size="10" maxlength="10" value="<?php echo $data1;?>">
              <font face="Arial" size="2">a</font>
              <input type="text" name="data2" size="10" maxlength="10" value="<?php echo $data2;?>">
            </td>
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Origem</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE">
              <select name="cp_origem">
			  	<option></option>
                <option value='E' <?php if($cp_origem == 'E') echo 'selected'?>>Estoque</option>
                <option value='F' <?php if($cp_origem == 'F') echo 'selected'?>>Financeiro</option>
              </select>
            </td>
		  </tr>
          <tr>
			<td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Usuário</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE">
				<select name="cp_codusu" id="cp_codusu">
					<option></option>
					<?php
					while ($dtUsuario = $bdsqlUsuario->result_query()) {
						$codusuario3 = $dtUsuario['CODUSU'];
						$nomeusu3 = $dtUsuario['NOMEUSU'];
						
						echo "<option value='$codusuario3' ";
						if ($cp_codusu == $codusuario3) echo "selected";
						echo ">$nomeusu3</option>";
					}
					?>
				</select>
			</td>
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Conferido</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE">
                <select name="cb_conferido">
				<option></option>
		        <option value='SIM' <?php if ($cb_conferido == 'SIM') echo 'selected';?>>Sim</option>
		        <option value='NAO' <?php if ($cb_conferido == 'NAO') echo 'selected';?>>Não</option>
                </select>
            </td>
          </tr>
		  <tr>    
            <td bgcolor="#99AACC" bordercolor="#99AACC"><font face="Arial" size="2"><b>Situação</b></font></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE">		
              <select name="cb_codsituacao">
                <option></option>
<?php
	while ($dtSit = $bdsqlSit->result_query()) {
    	$codigo3 = $dtSit['CODSITUACAO'];
		$descricao3 = $dtSit['DESCRSITUACAO'];
		echo "<option value='$codigo3' ";
		if ($codigo3 == $cb_codsituacao) echo "selected";
		echo ">$descricao3</option>";
	}
?>
              </select>
			</td>
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Anexo Pagto</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE">
                <select name="cb_anexo">
				<option></option>
		        <option value='SIM' <?php if ($cb_anexo == 'SIM') echo 'selected';?>>Sim</option>
		        <option value='NAO' <?php if ($cb_anexo == 'NAO') echo 'selected';?>>Não</option>
                </select>
            </td>
          </tr>
		  
          <tr> 
            <td bgcolor="#99AACC" bordercolor="#99AACC" width="120"><b><font face="Arial" size="2">Centro Resultado</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE" colspan="3"> 
              <select name="cb_codcencus">
				<option></option>
<?php
	while ($dtCus = $bdsqlCus->result_query()) {
    	$codcencus2 = $dtCus['CODCENCUS'];
		$descrcencus2 = $dtCus['DESCRCENCUS'];
		$ativocus2 = $dtCus['ATIVO'];
		$nomeusu2 = $dtCus['NOMEUSU'];
		$nomeusubv2 = $dtCus['NOMEUSUBV'];
		
		$dtCus['NOMEUSU'] = $dtCus['NOMEUSUBV'] = '';
		
		if ($nomeusu2 == 'SUP') $nomeusu2 = '';
		
		if ($ativocus2 == 'N') $ativocus3 = '(INATIVO)';
		else $ativocus3 = '';
		
		echo "<option value='$codcencus2' ";
		if ($codcencus2 == $cb_codcencus) echo "selected";
		echo ">$descrcencus2 $ativocus3 ($codcencus2)";
		if ($nomeusu2 <> '') echo " ($nomeusu2)";
		if ($nomeusubv2 <> '' && $nomeusu2 <> $nomeusubv2) echo " ($nomeusubv2)";
		echo "</option>";
	}
?>
              </select>
            </td>
		  </tr>
		  
		  <tr>
			<td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Natureza</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE" colspan="3">
              <select name="cb_codnat">
                <?php if ($ck_grupo <> 'GerInd') { ?> <option></option> <?php } ?>
<?php
	while ($dtNat = $bdsqlNat->result_query()) {
    	$codnat2 = $dtNat['CODNAT'];
		$descnat2 = $dtNat['DESCRNAT'];
		$natvalidada2 = $dtNat['INCRESULT'];
		$natconsumo2 = $dtNat['AD_CONSUMO'];
		$natunica2 = $dtNat['AD_UNICA'];
		$natfolha2 = $dtNat['AD_FOLHA'];
		$natsemmeta2 = $dtNat['AD_SEMMETA'];
		
		$dtNat['INCRESULT'] = $dtNat['AD_CONSUMO'] = $dtNat['AD_UNICA'] = $dtNat['AD_FOLHA'] = $dtNat['AD_SEMMETA'] = '';
		
		echo "<option value='$codnat2' ";
		if ($codnat2 == $cb_codnat) echo "selected";
		echo ">$descnat2";
		if ($natvalidada2 == 'S') echo " (VA)";
		else if ($natconsumo2 == 'S') echo " (CS)";
		else if ($natunica2 == 'S') echo " (UN)";
		else if ($natfolha2 == 'S') echo " (FO)";
		else if ($natsemmeta2 == 'S') echo " (SM)";
		echo " ($codnat2)</option>";
	}
?>
              </select>
			</td>
          </tr>
		  
		  <tr>
			<td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Tipo Natureza</font></b></td>
			<td bgcolor="#EEEEEE" bordercolor="#EEEEEE" colspan="3">
			  <table border="1" cellspacing="1" cellpadding="0" bordercolor="#FFFFFF" bgcolor="#000000" width="470">
				<tr>
				  <td bgcolor="#FFBBAA" bordercolor="#FFBBAA" title="Naturezas validadas">
				    <input type="checkbox" name="cb_natvalidada" value="S" <?php if ($cb_natvalidada) echo 'checked';?>>
					<font face="Arial" size="2">Validada</font></b></td>
				  </td>
				  <td bgcolor="#FFEEAA" bordercolor="#FFEEAA" title="Naturezas de consumo">
					<input type="checkbox" name="cb_natconsumo" value="S" <?php if ($cb_natconsumo) echo 'checked';?>>
					<font face="Arial" size="2">Consumo</font>
				  </td>
				  <td bgcolor="#CCDDEE" bordercolor="#CCDDEE" title="Naturezas de conta única">
					<input type="checkbox" name="cb_natunica" value="S" <?php if ($cb_natunica) echo 'checked';?>>
					<font face="Arial" size="2">Única</font>
				  </td>
				  <td bgcolor="#DDEEAA" bordercolor="#DDEEAA" title="Naturezas de folha">
					<input type="checkbox" name="cb_natfolha" value="S" <?php if ($cb_natfolha) echo 'checked';?>>
					<font face="Arial" size="2">Folha</font>
				  </td>
				  <td bgcolor="#CCBBEE" bordercolor="#CCBBEE" title="Naturezas sem meta">
					<input type="checkbox" name="cb_natsemmeta" value="S" <?php if ($cb_natsemmeta) echo 'checked';?>>
					<font face="Arial" size="2">Sem Meta</font>
				  </td>
				  <td bgcolor="#FFFFFF" bordercolor="#FFFFFF" title="Outras Naturezas">
					<input type="checkbox" name="cb_natoutras" value="S" <?php if ($cb_natoutras) echo 'checked';?>>
					<font face="Arial" size="2">Outras</font>
				  </td>
				</tr>
			  </table>
			</td>
		  </tr>
		  <tr>
			<td bgcolor="#99AACC" bordercolor="#99AACC">
			  <b><font face="Arial" size="2">Projetos</font></b>
			</td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE">
              <select name="codproj">
                <option></option>
                   <?php
	
	while ($dtProj = $bdsqlProjeto->result_query()) {
    	$codproj3 = $dtProj['CODPROJ'];
		$descproj3 = $dtProj['IDENTIFICACAO'];
		echo "<option value='$codproj3' ";
		if ($codproj3 == $codproj) echo "selected";
		echo ">$descproj3</option>";
	}
					?>
			  </select>
			</td>
            <td bgcolor="#99AACC" bordercolor="#99AACC" title="Conferido pela Contabilidade"><b><font face="Arial" size="2">Conf. Contab.</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE">
              <select name="cb_conf_contab">
			    <option></option>
				<option value='S' <?php if ($cb_conf_contab == 'S') echo 'selected';?>>Sim</option>
		        <option value='N' <?php if ($cb_conf_contab == 'N') echo 'selected';?>>Não</option>
			  </select>
            </td>
          </tr>
		  <tr>
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Tipo Título</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE" colspan="3">
              <div>
                <select name="sl_codtiptit">
                  <option></option>
                  <?php
	
	while ($dtTitulo = $bdsqlTitulo->result_query()) {
    	$codtiptit = $dtTitulo['CODTIPTIT'];
		$descrtiptit = $dtTitulo['DESCRTIPTIT'];
		if ($descrtiptit == '<SEM TIPO DE TITULO>') $descrtiptit = 'SEM TIPO DE TITULO';
		echo "<option value='$codtiptit' ";
		if ($sl_codtiptit == $codtiptit) echo "selected";
		echo ">$codtiptit - $descrtiptit</option>";
	}
?>
                </select>
              </div>
            </td>
          </tr>
		  <tr>
		  	<td valign="middle" bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Apenas c/ Acréscimo</font></b></td>
            <td valign="middle" bgcolor="#EEEEEE" bordercolor="#EEEEEE"><input name="cb_comjuros" type="checkbox" id="cb_comjuros" value="S" <?php if ($cb_comjuros) echo 'checked'; ?>></td>
			<td valign="middle" bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Apenas c/ Desconto</font></b></td>
            <td valign="middle" bgcolor="#EEEEEE" bordercolor="#EEEEEE"><input name="cb_comdesc" type="checkbox" id="cb_comdesc" value="S" <?php if ($cb_comdesc) echo 'checked'; ?>></td>
          </tr>
          <tr>
            <td bgcolor="#DDDDDD" bordercolor="#DDDDDD" align="center" colspan="4">
			  <input type="submit" name="btnbuscar" value="Buscar">
            </td>
          </tr>
        </table>
	  </form>
<?php
	if ($btnbuscar) {
?>
	<form name="form2" method="post" action="duppagas.php?PHPSESSID=<?php echo $PHPSESSID;?>&alterar=true&btnbuscar=true&todos=N&codparc=<?php echo $codparc;?>&cp_nomeparc=<?php echo $cp_nomeparc;?>&codemp=<?php echo $codemp;?>&numnota=<?php echo $numnota;?>&empresas=<?php echo $empresas;?>&data1=<?php echo $data1;?>&data2=<?php echo $data2;?>&data3=<?php echo $data3;?>&data4=<?php echo $data4;?>&dataneg1=<?php echo $dataneg1;?>&dataneg2=<?php echo $dataneg2;?>&dataentsai1=<?php echo $dataentsai1;?>&dataentsai2=<?php echo $dataentsai2;?>&cb_compensado=<?php echo $cb_compensado;?>&cb_agruparmatriz=<?php echo $cb_agruparmatriz;?>&cp_origem=<?php echo $cp_origem;?>&cp_codusu=<?php echo $cp_codusu;?>&cb_conferido=<?php echo $cb_conferido;?>&cb_codsituacao=<?php echo $cb_codsituacao;?>&cb_anexo=<?php echo $cb_anexo;?>&cb_codnat=<?php echo $cb_codnat;?>&cb_codcencus=<?php echo $cb_codcencus;?>&cb_natvalidada=<?php echo $cb_natvalidada;?>&cb_natconsumo=<?php echo $cb_natconsumo;?>&cb_natunica=<?php echo $cb_natunica;?>&cb_natfolha=<?php echo $cb_natfolha;?>&cb_natsemmeta=<?php echo $cb_natsemmeta;?>&cb_natoutras=<?php echo $cb_natoutras;?>&codproj=<?php echo $codproj;?>&sl_codtiptit=<?php echo $sl_codtiptit;?>&cb_comjuros=<?php echo $cb_comjuros;?>&cb_comdesc=<?php echo $cb_comdesc;?>&cb_conf_contab=<?php echo $cb_conf_contab;?>">
      <table border="1" cellspacing="1" cellpadding="1" align="center" bordercolor="#FFFFFF" bgcolor="#CCCCCC" width="98%">
        <tr bgcolor="#BBBBBB" bordercolor="#BBBBBB"> 
          <td bgcolor="#FAEFCC" bordercolor="#FAEFCC"> 
            <div align="center"><b><font face="Arial" size="2">Emp</font></b></div>
          </td>
          <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
            <div align="center"><b><font face="Arial" size="2">NF</font></b></div>
          </td>
          <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
            <div align="center"><b><font face="Arial" size="2">NºÚnico</font></b></div>
          </td>
          <td bgcolor="#FAEFCC" bordercolor="#FAEFCC"> 
            <div align="center"><b><font face="Arial" size="2">Emiss&atilde;o</font></b></div>
          </td>
          <td bgcolor="#FAEFCC" bordercolor="#FAEFCC"> 
            <div align="center"><b><font face="Arial" size="2">Venc</font></b></div>
          </td>
          <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
            <div align="center"><b><font face="Arial" size="2">Baixa</font></b></div>
          </td>
          <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
            <div align="center"><b><font face="Arial" size="2">Solicit</font></b></div>
          </td>
          <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
            <div align="center"><b><font face="Arial" size="2">Liberador</font></b></div>
          </td>
          <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
            <div align="center"><b><font face="Arial" size="2">Histórico</font></b></div>
          </td>
          <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
            <div align="center"><b><font face="Arial" size="2">Orig</font></b></div>
          </td>
          <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
            <div align="center"><b><font face="Arial" size="2">Conf</font></b></div>
          </td>
		  <td bordercolor="#FAEFCC" bgcolor="#FAEFCC" title="Conferido pela Contabilidade">
            <div align="center"><b><font face="Arial" size="2">Ctb</font></b></div>
          </td>
          <td bordercolor="#FAEFCC" bgcolor="#FAEFCC" title="Arquivos Anexados"> 
            <div align="center"><b><font face="Arial" size="2"><img src="img/anexo2.jpg" border="0" width="15"></font></b></div>
          </td>
          <td bordercolor="#FAEFCC" bgcolor="#FAEFCC" title="Arquivos Anexados de pagamento"> 
            <div align="center"><b><font face="Arial" size="2"><img src="img/anexo2.jpg" border="0" width="15"></font></b></div>
          </td>
          <td bordercolor="#FAEFCC" bgcolor="#FAEFCC">
            <div align="center"><b><font face="Arial" size="2">Vlr. Desdob</font></b></div>
          </td>		  
          <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
            <div align="center"><b><font face="Arial" size="2">Vlr. Pago</font></b></div>
          </td>
		  <td bordercolor="#FAEFCC" bgcolor="#FAEFCC" title="Desconto + ISS"> 
            <div align="center"><b><font face="Arial" size="2">Vlr. Desconto</font></b></div>
          </td>
		  <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
            <div align="center"><b><font face="Arial" size="2">Vlr. Acréscimo</font></b></div>
          </td>
        </tr>
<?php
	$parceiroant = $parceiro = $numnota = $dtneg = $dhbaixa = '';
	$valor = $soma = $somaParc = $somavlrdesdob = $somavlrdesdobParc = $somavlrdesc = $somavlrdescParc = $somavlracres = $somavlracresParc = 0;
	
	if ($cont > 0) {
		for ($cont=0; $cont<sizeof($vetor); $cont++) {
			$visualizar = true;
			$alteradoctb = false;
			$codsolicitante = $nomesolicitante = $nomeusuautorizou = '';
		    
			$parceiro = $vetor[$cont]['parceiro'];
			$nomeparc = $vetor[$cont]['nomeparc'];
			
			if ($parceiroant <> $parceiro) {
				if ($somaParc > 0) {
					echo "<tr><td bgcolor='#FFFFFF' align='center' colspan='14'><font size='2' face='Arial' color='#003366'><b>Total do Parceiro</b></font></td>";
					echo "<td bgcolor='#EEEEEE' bordercolor='#EEEEEE' align='right'><font size='2' face='Arial' color='#003366'><b>".number_format($somavlrdesdobParc, 2, ',', '.')."</b></font></td>";
					echo "<td bgcolor='#FFFFFF' align='right'><font size='2' face='Arial' color='#003366'><b>".number_format(str_replace(',','.',$somaParc), 2, ',', '.')."</b></font></td>";
					echo "<td bgcolor='#FFFFFF' align='right'><font size='2' face='Arial' color='#003366'><b>".number_format(str_replace(',','.',$somavlrdescParc), 2, ',', '.')."</b></font></td>";
					echo "<td bgcolor='#FFFFFF' align='right'><font size='2' face='Arial' color='#003366'><b>".number_format(str_replace(',','.',$somavlracresParc), 2, ',', '.')."</b></font></td>";
					echo "</tr>";
					
					$somaParc = $somavlrdescParc = $somavlracresParc = $somavlrdesdobParc = 0;
				}
			}
			
			$nufin = $vetor[$cont]['nufin'];
			$codemp = $vetor[$cont]['codemp'];
			$nunota = $vetor[$cont]['nunota'];
			$numnota = $vetor[$cont]['numnota'];
			$desdob = $vetor[$cont]['desdob'];
			$dtneg = $vetor[$cont]['dtneg'];
			$dtvenc = $vetor[$cont]['dtvenc'];
			$dhbaixa = $vetor[$cont]['dhbaixa'];
			$historico = $vetor[$cont]['historico'];
			$nomeusuario_fin = $vetor[$cont]['nomeusuario_fin'];
			$nomeusuario_cab = $vetor[$cont]['nomeusuario_cab'];
			$codusuario_cab = $vetor[$cont]['codusuario_cab'];
			$codusufin = $vetor[$cont]['codusufin'];
			$origem = $vetor[$cont]['origem'];
			$ad_conferido = $vetor[$cont]['ad_conferido'];
			$ad_conf_contab = $vetor[$cont]['ad_conf_contab'];
			$ad_numpedido = $vetor[$cont]['ad_numpedido'];
			$desdobramento = $vetor[$cont]['desdobramento'];
			
			if ($nunota != '') {
				// Liberação de Limite
				$sql1 = "SELECT U1.CODUSU, U1.NOMEUSU, U2.NOMEUSU AS USUAUTORIZOU
						 FROM TSILIB L, TSIUSU U1, TSIUSU U2, TGFVAR V
						 WHERE V.NUNOTA = $nunota
						 AND L.NUCHAVE = V.NUNOTAORIG AND L.TABELA = 'TGFCAB'
						 AND U1.CODUSU = L.CODUSUSOLICIT AND U2.CODUSU = L.CODUSULIB
						 ORDER BY L.DHLIB";
				if ($codemp > 500) {
					$bdsql1 = new BDORACLE();
					$bdsql1->desconecta();
					$bdsql1->conectaEX($usubanco);
					$bdsql1->execute_query($sql1,$bdsql1->conexao);
				} else {
					$bdsql1 = new BDORACLE();
					$bdsql1->desconecta();
					$bdsql1->conecta($usubanco);
					$bdsql1->execute_query($sql1,$bdsql1->conexao);
				}
				while ($dt1 = $bdsql1->result_query()) {
					$codsolicitante = $dt1['CODUSU'];
					$nomesolicitante = $dt1['NOMEUSU'];
					$nomeusuautorizou = $dt1['USUAUTORIZOU'];
					
					$dt1['CODUSU'] = $dt1['NOMEUSU'] = $dt1['USUAUTORIZOU'] = '';
				}
				
				if ($codsolicitante == '') {
					// Liberação de Limite
					$sql1 = "SELECT U1.CODUSU, U1.NOMEUSU, U2.NOMEUSU AS USUAUTORIZOU
							 FROM TSILIB L, TSIUSU U1, TSIUSU U2
							 WHERE U1.CODUSU = L.CODUSUSOLICIT AND U2.CODUSU = L.CODUSULIB
							 AND L.NUCHAVE = $nunota
							 AND L.TABELA = 'TGFCAB'
							 ORDER BY L.DHLIB";
					if ($codemp > 500) {
						$bdsql1 = new BDORACLE();
						$bdsql1->desconecta();
						$bdsql1->conectaEX($usubanco);
						$bdsql1->execute_query($sql1,$bdsql1->conexao);
					} else {
						$bdsql1 = new BDORACLE();
						$bdsql1->desconecta();
						$bdsql1->conecta($usubanco);
						$bdsql1->execute_query($sql1,$bdsql1->conexao);
					}
					while ($dt1 = $bdsql1->result_query()) {
						$codsolicitante = $dt1['CODUSU'];
						$nomesolicitante = $dt1['NOMEUSU'];
						$nomeusuautorizou = $dt1['USUAUTORIZOU'];
						
						$dt1['CODUSU'] = $dt1['NOMEUSU'] = $dt1['USUAUTORIZOU'] = '';
					}
				}
			}
			
			if ($ad_numpedido != '' && $nomeusuautorizou == '') {
				// Buscando usuário quem autorizou o pedido
				$sql1 = "SELECT U2.NOMEUSU AS USUAUTORIZOU
						 FROM TGFCAB C, TSILIB L, TSIUSU U2
						 WHERE C.NUMNOTA = $ad_numpedido
						 AND C.CODPARC = $parceiro
						 AND C.CODEMP = $codemp
						 AND C.TIPMOV = 'O'
						 AND L.NUCHAVE = C.NUNOTA
						 AND U2.CODUSU = L.CODUSULIB";
				if ($codemp > 500) {
					$bdsql1 = new BDORACLE();
					$bdsql1->desconecta();
					$bdsql1->conectaEX($usubanco);
					$bdsql1->execute_query($sql1,$bdsql1->conexao);
				} else {
					$bdsql1 = new BDORACLE();
					$bdsql1->desconecta();
					$bdsql1->conecta($usubanco);
					$bdsql1->execute_query($sql1,$bdsql1->conexao);
				}
				while ($dt1 = $bdsql1->result_query()) {
					$nomeusuautorizou = $dt1['USUAUTORIZOU'];
					
					$dt1['USUAUTORIZOU'] = '';
				}
				
				if ($nomeusuautorizou == '') {
					$nomeusuabriu = '';
					
					// Buscando usuário quem abriu o pedido
					$sql1 = "SELECT U2.NOMEUSU AS USUABRIU
							 FROM TGFCAB C, TSIUSU U2
							 WHERE C.NUMNOTA = $ad_numpedido
							 AND C.CODPARC = $parceiro
							 AND C.CODEMP = $codemp
							 AND C.TIPMOV = 'O'
							 AND U2.CODUSU = C.CODUSU";
					if ($codemp > 500) {
						$bdsql1 = new BDORACLE();
						$bdsql1->desconecta();
						$bdsql1->conectaEX($usubanco);
						$bdsql1->execute_query($sql1,$bdsql1->conexao);
					} else {
						$bdsql1 = new BDORACLE();
						$bdsql1->desconecta();
						$bdsql1->conecta($usubanco);
						$bdsql1->execute_query($sql1,$bdsql1->conexao);
					}
					while ($dt1 = $bdsql1->result_query()) {
						$nomeusuabriu = $dt1['USUABRIU'];
						
						$nomeusuautorizou = $nomeusuabriu;
						
						$dt1['USUABRIU'] = '';
					}
					
					if ($nomeusuautorizou == '') $nomeusuautorizou = $ad_numpedido;
				}
			}
			
			if ($cp_codusu == '' || ($codsolicitante == '' && $codusuario_cab == $cp_codusu) || $cp_codusu == $codsolicitante || $cp_codusu == $codusufin) {
				if ($parceiroant <> $parceiro) {
					$parceiroant = $parceiro;
					echo "<tr><td bgcolor='#EEEEEE' colspan='18'><font size='2' face='Arial' color='#003366'><b>$parceiro&nbsp;-&nbsp;$nomeparc</b></font></td></tr>";
				}
				
				if ($nomesolicitante == '') {
					if ($nomeusuario_cab == '') $nomesolicitante = $nomeusuario_fin;
					else $nomesolicitante = $nomeusuario_cab;
				}
				
				if ($ad_conf_contab == '') $ad_conf_contab = 'N';
				
				// Buscando Arquivo
				$sql1 = "SELECT DESCRICAO, ARQUIVO
						 FROM TSIATA
						 WHERE CODATA = '$nufin' AND TIPO = 'C'
						 ORDER BY ARQUIVO";
				if ($codemp > 500) {
					$bdsql1 = new BDORACLE();
					$bdsql1->desconecta();
					$bdsql1->conectaEX($usubanco);
					$bdsql1->execute_query($sql1,$bdsql1->conexao);
				} else {
					$bdsql1 = new BDORACLE();
					$bdsql1->desconecta();
					$bdsql1->conecta($usubanco);
					$bdsql1->execute_query($sql1,$bdsql1->conexao);
				}
				
				$contarquivo = $contarquivopgto = 0;
				$v_arquivo = $v_arquivopgto = '';
				
				while ($dt1 = $bdsql1->result_query()) {
					if (substr($dt1['DESCRICAO'],0,4) == 'pgto'
						|| strstr(strtoupper($dt1['ARQUIVO']), 'PGTO') != ''
						|| strstr(strtoupper($dt1['ARQUIVO']), 'PAGTO') != ''
						|| strstr(strtoupper($dt1['ARQUIVO']), 'PAGAMENTO') != '') {
						$contarquivopgto++;
						
						$v_arquivopgto[$contarquivopgto]['arquivo'] = $dt1['ARQUIVO'];
					} else {
						$contarquivo++;
						
						$v_arquivo[$contarquivo]['arquivo'] = $dt1['ARQUIVO'];
					}
				}
				
				if ($alterar) {
					if ($codemp > 500) {
						$bdsqlInsertEX = new BDORACLE();
						$bdsqlInsertEX->desconecta();
						$bdsqlInsertEX->conectaEX($usubanco);
					} else {
						$bdsqlInsert = new BDORACLE();
						$bdsqlInsert->desconecta();
						$bdsqlInsert->conecta($usubanco);
					}
					
					// Conferir Contabilidade
					if ($btnconferirctb && $campoctb[$nufin] == 'S' && $contarquivo > 0) {
						if ($grupo == 'Ctb' || $grupo == 'Sec' || $usuario == 'RAIANE' || $usuario == 'LETICIA' || $usuario == 'SAMUEL') {
							if (substr($ad_conf_contab,0,1) <> 'S') {
								$contAltctb++;
								$alteradoctb = true;
								
								// Conferindo a contabilidade
								$sqlUpdate = "UPDATE TGFFIN SET AD_CONF_CONTAB = 'S', AD_CODUSUCTB = '$codusuario'
											  WHERE NUFIN = $nufin AND RECDESP = -1 AND DHBAIXA IS NOT NULL";
								if ($codemp > 500) {
									$bdsqlInsertEX->execute_query($sqlUpdate,$bdsqlInsertEX->conexao);
								} else {
									$bdsqlInsert->execute_query($sqlUpdate,$bdsqlInsert->conexao);
								}
								
								$ad_conf_contab = 'S';
								$historico .= " . Conferido a contabilidade por $usuario.";
							}
						}
					}
					if ($alteradoctb && $contAltctb > 0) {
						$textoctb = "<html><body><meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>";
						$textoctb .= "<table border='1' cellspacing='1' cellpadding='2' align='center' bordercolor='#FFFFFF' bgcolor='#FFFFFF' width='400'>";
						$textoctb .= "<tr><td bgcolor='#99AACC' align='center' colspan='4'><font size='2' face='Arial'><b>Conferido pela Contabilidade</b></font></td></tr>";
						$textoctb .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Parceiro</b></font></td>";
						$textoctb .= "<td bgcolor='#FFFFFF' colspan='3'><font size='2' face='Arial'>$parceiro&nbsp;-&nbsp;$nomeparc</font></td></tr>";
						
						$textoctb .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Empresa</b></font></td>";
						$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$codemp</font></td>";
						$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Origem</b></font></td>";
						$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
						if ($origem == "E") $textoctb .= "Estoque"; else $textoctb .= "Financeiro";
						$textoctb .= "</font></td></tr>";
						
						$textoctb .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>NF</b></font></td>";
						$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$numnota</font></td>";
						$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Parcela</b></font></td>";
						$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$desdobramento</font></td></tr>";
						$textoctb .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Emissão</b></font></td>";
						$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$dtneg</font></td>";
						$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Prorrogação</b></font></td>";
						$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$dtvenc</font></td></tr>";
						$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Baixa</b></font></td>";
						$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$dhbaixa</font></td></tr>";
						$textoctb .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Solicit</b></font></td>";
						$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$nomesolicitante</font></td>";
						$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Liberador</b></font></td>";
						$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$nomeusuautorizou</font></td></tr>";
						
						$textoctb .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Autorizado</b></font></td>";
						$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
						if ($autorizado == "S") $textoctb .= "Sim"; else $textoctb .= "Não";
						$textoctb .= "</font></td></tr>";
						
						$textoctb .= "<tr>";
						$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Conf Ctb</b></font></td>";
						$textoctb .= "<td bgcolor='#FFFFFF' colspan='3'><font size='2' face='Arial'>";
						if (substr($ad_conf_contab,0,1) == "S") $textoctb .= "Sim"; else $textoctb .= "Não";
						$textoctb .= "</font></td>";
						$textoctb .= "</tr>";
						
						$textoctb .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Valor</b></font></td>";
						$textoctb .= "<td bgcolor='#FFFFFF' colspan='3'><font size='2' face='Arial'>$valor</font></td></tr>";
						
						$textoctb .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Histórico</b></font></td>";
						$textoctb .= "<td bgcolor='#FFFFFF' colspan='3'><font size='2' face='Arial'>$historico</font></td></tr>";
						
						$para = array();
						$textoctb .= "</table></body></html>";
						
						$mail = new PHPMailer(); // Cria a instância
						$mail->SetLanguage("br"); // Define o Idioma
						$mail->CharSet = "iso-8859-1"; // Define a Codificação
						$mail->IsSMTP(); // Define que será enviado por SMTP
						$mail->Host = $ipservidoremail; // Servidor SMTP
						$mail->SMTPAuth = $ck_autenticacaoemail; // Caso o servidor SMTP precise de autenticação
						$mail->Username = $ck_remetemail; // Usuário ou E-mail para autenticação no SMTP
						$mail->Password = $ck_senhaemail; // Senha do E-mail
						$mail->IsHTML(true); // Enviar como HTML
						$mail->From = 'aguanativa@aguanativa.com.br'; // Define o Remetente
						$mail->FromName = $usuario; // Nome do Remetente
						$mail->Subject = "Conferido pela Contabilidade"; // Define o Assunto
						$mail->Body = $textoctb; // Corpo da mensagem em formato HTML
						
						if ($emailsolicitante <> 'suporte@aguanativa.com.br') $mail->AddAddress("$emailsolicitante",""); // Email e Nome do destinatário		
						
						$para[0] = $ck_email;
						$para[1] = 'lauro@aguanativa.com.br';
						
						for ($j=0; $j<sizeof($para); $j++) {
							if ($para[$j] <> '') {
								$mail->AddCC($para[$j],""); // Email e Nome do destinatário	
							}
						}
						
						if ($ck_enviaemail) {
							// Fazemos o envio do email
							if (!$mail->Send()) print "<br>Erro: ".$mail->ErrorInfo;
						}
					}
					
					// COMMIT
					if ($bdsqlInsert->conexao <> '') $bdsqlInsert->commit($bdsqlInsert->conexao);
					if ($bdsqlInsertEX->conexao <> '') $bdsqlInsertEX->commit($bdsqlInsertEX->conexao);
				}
				
				if ($cb_anexo == 'SIM') {
					$visualizar = false;
					if ($contarquivopgto > 0) $visualizar = true;
				} else if ($cb_anexo == 'NAO') {
					$visualizar = false;
					if ($contarquivopgto == 0) $visualizar = true;
				}
				
				if ($visualizar) {
					$soma = $soma + str_replace(',','.',$vetor[$cont]['vlrbaixa']);
					$somaParc = $somaParc + str_replace(',','.',$vetor[$cont]['vlrbaixa']);		
					$valor = number_format(str_replace(',','.',$vetor[$cont]['vlrbaixa']), 2, ',', '.');
					
					$somavlrdesc = $somavlrdesc + str_replace(',','.',$vetor[$cont]['vlrdesc']) + str_replace(',','.',$vetor[$cont]['vlriss']);
					$somavlrdescParc = $somavlrdescParc + str_replace(',','.',$vetor[$cont]['vlrdesc']) + str_replace(',','.',$vetor[$cont]['vlriss']);
					$valordesc = number_format(str_replace(',','.',$vetor[$cont]['vlrdesc']) + str_replace(',','.',$vetor[$cont]['vlriss']), 2, ',', '.');
					
					$somavlracres = $somavlracres + str_replace(',','.',$vetor[$cont]['vlracres']);
					$somavlracresParc = $somavlracresParc + str_replace(',','.',$vetor[$cont]['vlracres']);
					$valoracres = number_format(str_replace(',','.',$vetor[$cont]['vlracres']), 2, ',', '.');
					
					$somavlrdesdob = $somavlrdesdob + str_replace(',','.',$vetor[$cont]['vlrdesdob']);
					$somavlrdesdobParc = $somavlrdesdobParc + str_replace(',','.',$vetor[$cont]['vlrdesdob']);		
					$valordesdob = number_format(str_replace(',','.',$vetor[$cont]['vlrdesdob']), 2, ',', '.');
?>
		<tr>
          <td bgcolor="#FFFFFF" align="center"><font size="2" face="Arial"> 
            <?php echo $codemp;?>
            </font></td>
          <td bgcolor="#FFFFFF" align="right">
<?php if ($origem == 'E') { ?> <a href="itens.php?PHPSESSID=<?php echo $PHPSESSID;?>&nunota=<?php echo $nunota;?>&numnota=<?php echo $numnota;?>&codemp=<?php echo $codemp;?>&parceiro=<?php echo $parceiro;?>"><font size="2" face="Arial"><?php echo $numnota;?></a></font>
<?php } else { ?> <font size="2" face="Arial"><?php echo $numnota;?></font> <?php } ?>
          </td>
		  <td bgcolor="#FFFFFF" align="center">
			<font size="2" face="Arial"><a href="financeiro.php?PHPSESSID=<?php echo $PHPSESSID;?>&nufin=<?php echo $nufin;?>&codemp=<?php echo $codemp;?>"><?php echo $nufin;?></a></font>
		  </td>
          <td bgcolor="#FFFFFF" align="center"><font size="2" face="Arial"> 
            <?php echo $dtneg;?>
            </font></td>
          <td bgcolor="#FFFFFF" align="center"><font size="2" face="Arial"> 
            <?php echo $dtvenc;?>
            </font></td>
          <td bgcolor="#FFFFFF" align="center"><font size="2" face="Arial"> 
            <?php echo $dhbaixa;?>
            </font></td>
          <td bgcolor="#FFFFFF"><font size="2" face="Arial"> 
            <?php echo $nomesolicitante;?>
            </font></td>
          <td bgcolor="#FFFFFF" title="Nº Pedido <?php echo $ad_numpedido;?>"><font size="2" face="Arial"> 
            <?php echo $nomeusuautorizou;?>
            </font></td>						
          <td bgcolor="#FFFFFF"><font size="2" face="Arial"> 
            <?php echo $historico;?>
            </font></td>
          <td bgcolor="#FFFFFF" align="center"><font size="2" face="Arial"> 
            <?php echo $origem;?>
            </font></td>
          <td bgcolor="#FFFFFF" align="center"><font size="2" face="Arial"> 
            <?php echo substr($ad_conferido,0,1);?>
            </font></td>
          <td bgcolor="#FFFFFF" align="center">
			<input type="checkbox" name="<?php echo 'campoctb['.$nufin.']';?>" value="S" <?php if ($grupo <> 'Ctb' && $grupo <> 'Sec' && $usuario <> 'RAIANE' && $usuario <> 'LETICIA' && $usuario <> 'SAMUEL') echo 'disabled';?> <?php if ($contarquivo == 0) { echo ' disabled '; echo " title='Para Conferir o lançamento é preciso anexar o arquivo.' "; } ?> <?php if (($todosctb == 'S' && ($grupo == 'Dir' || $grupo == 'Ctb' || $grupo == 'Sec' || $usuario == 'RAIANE' || $usuario == 'LETICIA' || $usuario == 'SAMUEL')) || substr($ad_conf_contab,0,1) == 'S') echo 'checked'?>>
		  </td>
			
		  <td bgcolor="#FFFFFF" align="center">
<?php
		// Vetor Arquivo
		if ($contarquivo > 0) {
			for ($a=1; $a<=sizeof($v_arquivo); $a++) {
            	$arquivo4 = $v_arquivo[$a]['arquivo'];
				$arquivo5 = substr($arquivo4,9,60);
?>
				<a href="<?php echo $arquivo4;?>" target="_blank" title="<?php echo $arquivo5;?>"><img src="img/anexo1.jpg" border="0" width="15"></a>
<?php 
			}
		} else echo '&nbsp;';
?>
		  </td>
		  
		  <td bgcolor="#FFFFFF" align="center">
<?php
		// Vetor Arquivo Pagamento
		if ($contarquivopgto > 0) {
			for ($a=1; $a<=sizeof($v_arquivopgto); $a++) {
            	$arquivo4 = $v_arquivopgto[$a]['arquivo'];
				$arquivo5 = substr($arquivo4,9,90);
?>
				<a href="<?php echo $arquivo4;?>" target="_blank" title="<?php echo $arquivo5;?>"><img src="img/anexo4.jpg" border="0" width="17"></a>
<?php 
			}
		} else echo '&nbsp;';
?>
		  </td>
		  
          <td bgcolor="#EEEEEE" bordercolor="#EEEEEE" align="right"><font size="2" face="Arial">
            <?php echo $valordesdob;?>
            </font></td>
          <td bgcolor="#FFFFFF" align="right"><font size="2" face="Arial"> 
            <?php echo $valor;?>
            </font></td>
		  <td bgcolor="#FFFFFF" align="right"><font size="2" face="Arial"> 
            <?php echo $valordesc;?>
            </font></td>
		  <td bgcolor="#FFFFFF" align="right"><font size="2" face="Arial"> 
            <?php echo $valoracres;?>
            </font></td>
        </tr>
<?php
				}
			}
		}
	}
	
	echo "<tr>";
	echo "<td bgcolor='#FFFFFF' align='center' colspan='14'><font size='2' face='Arial' color='#003366'><b>Total do Parceiro</b></font></td>";
	echo "<td bgcolor='#EEEEEE' bordercolor='#EEEEEE' align='right'><font size='2' face='Arial' color='#003366'><b>".number_format($somavlrdesdobParc, 2, ',', '.')."</b></font></td>";
	echo "<td bgcolor='#FFFFFF' align='right'><font size='2' face='Arial' color='#003366'><b>".number_format(str_replace(',','.',$somaParc), 2, ',', '.')."</b></font></td>";
	echo "<td bgcolor='#FFFFFF' align='right'><font size='2' face='Arial' color='#003366'><b>".number_format(str_replace(',','.',$somavlrdescParc), 2, ',', '.')."</b></font></td>";
	echo "<td bgcolor='#FFFFFF' align='right'><font size='2' face='Arial' color='#003366'><b>".number_format(str_replace(',','.',$somavlracresParc), 2, ',', '.')."</b></font></td>";
    echo "</tr>";
	
    $somavlrdesdob = number_format(str_replace(',','.',$somavlrdesdob), 2, ',', '.');
	$soma = number_format(str_replace(',','.',$soma), 2, ',', '.');
	$somavlrdesc = number_format(str_replace(',','.',$somavlrdesc), 2, ',', '.');
	$somavlracres = number_format(str_replace(',','.',$somavlracres), 2, ',', '.');
?>
        <tr bgcolor="#BBBBBB" bordercolor="#BBBBBB"> 
          <td colspan="14" bgcolor="#FAEFCC" bordercolor="#FAEFCC"> 
            <div align="center"><b><font face="Arial" size="2">Total</font></b></div>
          </td>
          <td align="right" bordercolor="#FAEFCC" bgcolor="#FAEFCC"><b><font face="Arial" size="2"> 
            <?php echo $somavlrdesdob;?>
            </font></b></td>
          <td align="right" bordercolor="#FAEFCC" bgcolor="#FAEFCC"><b><font face="Arial" size="2"> 
            <?php echo $soma;?>
            </font></b></td>
          <td align="right" bordercolor="#FAEFCC" bgcolor="#FAEFCC"><b><font face="Arial" size="2"> 
            <?php echo $somavlrdesc;?>
            </font></b></td>
          <td align="right" bordercolor="#FAEFCC" bgcolor="#FAEFCC"><b><font face="Arial" size="2"> 
            <?php echo $somavlracres;?>
            </font></b></td>
        </tr>
      </table>
      <br>
	  
<?php if ($grupo == 'Ctb' || $grupo == 'Sec' || $usuario == 'RAIANE' || $usuario == 'LETICIA' || $usuario == 'SAMUEL') { ?>
      <br>
      <div align="center">
        <input type="submit" name="btnconferirctb" value="Conferir Contabilidade">
      </div>
<?php } ?>

	</form>
	  
<?php } ?>
    </td>
  </tr>
</table>
<br>
</body>
<?php
	} else {
		echo "<script>alert('A sessão expirou, faça login novamente!')</script>";
		echo "<script>window.location = 'index.php'</script>";
	}
?>
</html>
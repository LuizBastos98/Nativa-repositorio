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
<title>Duplicatas a Pagar</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script language="JavaScript">
<!--
function marcartodos(todos) {
	var marcar;
	if (todos.checked) {marcar = 'S';}
	if (! todos.checked) {marcar = 'N';}
	var newURL = 'duppagar.php?PHPSESSID=<?php echo $PHPSESSID;?>&btnbuscar=true&autorizados=<?php echo $autorizados;?>&cb_provisao=<?php echo $cb_provisao;?>&todos='+marcar+'&data1=<?php echo $data1;?>&data2=<?php echo $data2;?>&dataneg1=<?php echo $dataneg1;?>&dataneg2=<?php echo $dataneg2;?>&dataalter1=<?php echo $dataalter1;?>&dataalter2=<?php echo $dataalter2;?>&codparc=<?php echo $codparc;?>&cp_nomeparc=<?php echo $cp_nomeparc;?>&codemp=<?php echo $codemp;?>&empresas=<?php echo $empresas;?>&cb_agruparmatriz=<?php echo $cb_agruparmatriz;?>&cb_anexo=<?php echo $cb_anexo;?>&cb_codnat=<?php echo $cb_codnat;?>&cb_codcencus=<?php echo $cb_codcencus;?>&codproj=<?php echo $codproj;?>&sl_codtiptit=<?php echo $sl_codtiptit;?>&cb_codsituacao=<?php echo $cb_codsituacao;?>&cb_agendado=<?php echo $cb_agendado;?>&cb_conferido=<?php echo $cb_conferido;?>&cb_conf_contab=<?php echo $cb_conf_contab;?>&cp_origem=<?php echo $cp_origem;?>&cp_codusu=<?php echo $cp_codusu;?>&numnota=<?php echo $numnota;?>&cb_natvalidada=<?php echo $cb_natvalidada;?>&cb_natconsumo=<?php echo $cb_natconsumo;?>&cb_natunica=<?php echo $cb_natunica;?>&cb_natfolha=<?php echo $cb_natfolha;?>&cb_natsemmeta=<?php echo $cb_natsemmeta;?>&cb_natoutras=<?php echo $cb_natoutras;?>';
	document.location.href=newURL
}
-->
</script>
<?php
if ($grupo <> '' && $usuariologado == $usuario && (($acesso_fin_receitas <> 'N' && $acesso_fin_receitas <> '') || ($acesso_fin_despesas <> 'N' && $acesso_fin_despesas <> ''))) {
	include_once("bdoraclemge2.php");
	include_once("classes/phpmailer/class.phpmailer.php");
	
	$data1 = trim($data1);
	$data2 = trim($data2);
	$dataneg1 = trim($dataneg1);
	$dataneg2 = trim($dataneg2);
	$dataentsai1 = trim($dataentsai1);
	$dataentsai2 = trim($dataentsai2);
	
	if (!$btnbuscar && $data1 == '' && $data2 == '' && $dataneg1 == '' && $dataneg2 == '' && $dataentsai1 == '' && $dataentsai2 == '') {
		$data1 = date("01/01/2000");
		$data2 = date("d/m/Y", strtotime("+15 days"));
		
		// Variaveis padrao
		if ($grupo == 'Dir' || $grupo == 'Fin' || $grupo == 'GerFin') {
			if ($cb_conferido == '' && $cb_provisao <> 'S') $cb_conferido = 'S';
			if ($cb_conf_contab == '' && $cb_conf_contab <> 'S') $cb_conf_contab = 'S';
			if ($cb_agendado == '' && $cb_provisao <> 'S') $cb_agendado = 'S';
			if ($cb_provisao == '') $cb_provisao = 'N';
			if ($autorizados == '') $autorizados = 'N';
		}
	}
	
	// Retirado devido travas supervisores.
	if ($grupo <> 'Dir' && $grupo <> 'Inf' && $grupo <> 'Sec' && $grupo <> 'Pes' && $grupo <> 'Cpr' && $grupo <> 'DirBV' && $grupo <> 'Fin' && $grupo <> 'GerFin' && $grupo <> 'Ctb' && $grupo <> 'Rec' && $grupo <> 'Ger' && $grupo <> 'Ope' && $cp_codusu == '' && $grupo <> 'GerCom' && $grupo <> 'GerInd') {
		$cp_codusu = $codusuario;
	}
	
	// Se Grupo Faturamento ver clientes.
	if ($grupo == 'Fat') $verapenasclientes = 'S';
	
	// Verificando se é Supervisor
	if ($grupo == 'Dir' || $grupo == 'Inf' || $grupo == 'Cpr' || $grupo == 'Sec' || $usuario == 'RAIANE' || $grupo == 'Rec' || $grupo == 'Fin' || $grupo == 'GerFin' || $grupo == 'DirBV' || $grupo == 'Ctb' || $grupo == 'Ger' || $grupo == 'Ope' || $grupo == 'GerInd') {
		// Os grupos acima sempre veem tudo.
		$temequipe = false;
	} else {
		$temequipe = true;
		if ($grupo == 'Com' || $grupo == 'GerCom') $codusuadicional = 509;	// SE COMERCIAL, VER AGENDA
	}
	
	// Lauro/Gleidson - 09/05/15 - Permitir Usuário ou CR
	if ($btnbuscar && $temequipe && $cp_codusu == '' && $cb_codcencus == '' && $grupo <> 'GerCom') {
		$mensagem_trava = "Atenção! Informe o USUÁRIO ou o CENTRO DE RESULTADO.";
		echo "<script>window.location = 'mensagem_trava.php?PHPSESSID=$PHPSESSID&mensagem_trava=$mensagem_trava';</script>";
		exit;
	}
	
	// Lista adicional Usuario
	$listaadicionalusu = $codusuario;
	
	// BUSCANDO USUARIOS
	$sqlUsuario = "SELECT DISTINCT(U.CODUSU), U.NOMEUSU
				   FROM TSIUSU U, TSIUSU U1 
				   WHERE U.CODGRUPO <> 20 AND (U.DTLIMACESSO >= SYSDATE-180 OR U.DTLIMACESSO IS NULL)
				   AND U1.CODUSU(+) = U.AD_CODUSUSUP ";
	if ($temequipe) $sqlUsuario .= " AND (U.AD_CODUSUSUP = '$codusuario' OR U.CODUSU = '$codusuario' OR U1.AD_CODUSUSUP = '$codusuario' OR U.CODUSU = '$codusuadicional' OR U.CODUSU = 509 OR U.CODUSU IN ($listaadicionalusu)) ";
	$sqlUsuario .= " ORDER BY U.NOMEUSU";
	$bdsqlUsuario = new BDORACLE();
	$bdsqlUsuario->desconecta();
	$bdsqlUsuario->conecta($usubanco);
	$bdsqlUsuario->execute_query($sqlUsuario,$bdsqlUsuario->conexao);
	
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
	
	if ($codparc <> '') {
		// Nome parceiro
		$sql1 = "SELECT P.NOMEPARC, P.CGC_CPF, P.IDENTINSCESTAD FROM TGFPAR P WHERE P.CODPARC = $codparc";
		$bdsql1 = new BDORACLE();
		$bdsql1->desconecta();
		$bdsql1->conecta($usubanco);
		$bdsql1->execute_query($sql1,$bdsql1->conexao);
		while ($dt1 = $bdsql1->result_query()) {
	    	$nomeparc = $dt1['NOMEPARC'];
			$cgc_cpf = $dt['CGC_CPF'];
			$identinscestad = $dt['IDENTINSCESTAD'];
		}
	}

	// BUSCANDO PROJETO
	$sqlProjeto = "SELECT R.CODPROJ, R.IDENTIFICACAO FROM TCSPRJ R ORDER BY R.IDENTIFICACAO";
	$bdsqlProjeto = new BDORACLE();
	$bdsqlProjeto->desconecta();
	$bdsqlProjeto->conecta($usubanco);
	$bdsqlProjeto->execute_query($sqlProjeto,$bdsqlProjeto->conexao);
	
	// Natureza
	$sqlNat = "SELECT CODNAT, DESCRNAT, INCRESULT, AD_CONSUMO, AD_UNICA, AD_FOLHA, N.AD_SEMMETA
			   FROM TGFNAT N
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
	if ($grupo <> 'Dir' && $grupo <> 'Inf' && $grupo <> 'Fin' && $grupo <> 'GerFin' && $grupo <> 'Ctb' && $grupo <> 'Sec' && $usuario <> 'RAIANE' && $grupo <> 'Rec') {
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
	
	if ($btnbuscar) {
		$cont = 0;
		$vetor = '';
		
		// Buscando contas a pagar
		if ($cb_agruparmatriz) {
			$sql = "SELECT DISTINCT(F.NUFIN), N.CODNAT, N.DESCRNAT, F.CODEMP, MAX(P.NOMEPARC) AS NOMEPARC, F.PROVISAO, F.NUFIN, P.CODPARCMATRIZ AS CODPARC,
				F.NUNOTA, F.NUMNOTA, F.CODNAT, F.CODTIPTIT, T.DESCRTIPTIT,
				F.DESDOBRAMENTO, F.DTNEG, F.DTVENC, F.HISTORICO, F.AUTORIZADO, 
				F.VLRDESDOB AS VALOR,
				F.AD_CONFERIDO, F.AD_AGENDADO, F.AD_CONF_CONTAB, F.AD_ENTCONTAB, F.ORIGEM, U3.NOMEUSU AS USUARIO_FIN, U3.EMAIL, F.CODUSU, F.AD_NUMPEDIDO,
				P.CLIENTE, P.SELECIONADO, 
				N.INCRESULT, N.AD_CONSUMO, N.AD_UNICA, N.AD_FOLHA, N.AD_SEMMETA,
				(SELECT U4.CODUSU FROM TGFCAB C4, TSIUSU U4
						WHERE C4.NUNOTA = F.NUNOTA AND U4.CODUSU = C4.CODUSU) AS CODUSU_CAB,
				(SELECT U4.NOMEUSU FROM TGFCAB C4, TSIUSU U4
						WHERE C4.NUNOTA = F.NUNOTA AND U4.CODUSU = C4.CODUSU) AS USUARIO_CAB						";
		} else {
			$sql = "SELECT DISTINCT(F.NUFIN), N.CODNAT, N.DESCRNAT, F.CODEMP, P.NOMEPARC, P.CGC_CPF, P.IDENTINSCESTAD, F.PROVISAO, F.NUFIN, F.CODPARC,
				F.NUNOTA, F.NUMNOTA, F.CODNAT, F.CODTIPTIT, T.DESCRTIPTIT,
				F.DESDOBRAMENTO, F.DTNEG, F.DTVENC, F.HISTORICO, F.AUTORIZADO, 
				F.VLRDESDOB AS VALOR,
				F.AD_CONFERIDO, F.AD_AGENDADO, F.AD_CONF_CONTAB, F.AD_ENTCONTAB, F.ORIGEM, U3.NOMEUSU AS USUARIO_FIN, 
				U3.EMAIL, F.CODUSU, F.AD_NUMPEDIDO, P.CLIENTE, P.SELECIONADO, 
				N.INCRESULT, N.AD_CONSUMO, N.AD_UNICA, N.AD_FOLHA, N.AD_SEMMETA,
				(SELECT U4.CODUSU FROM TGFCAB C4, TSIUSU U4
						WHERE C4.NUNOTA = F.NUNOTA AND U4.CODUSU = C4.CODUSU) AS CODUSU_CAB,
				(SELECT U4.NOMEUSU FROM TGFCAB C4, TSIUSU U4
						WHERE C4.NUNOTA = F.NUNOTA AND U4.CODUSU = C4.CODUSU) AS USUARIO_CAB ";
		}
		
		$sql .= " 	FROM TGFFIN F, TGFPAR P, TSIUSU U3, TGFNAT N, TGFTIT T
					WHERE F.RECDESP = -1 AND F.DHBAIXA IS NULL 
					AND P.CODPARC = F.CODPARC
					AND U3.CODUSU(+) = F.CODUSU 
					AND N.CODNAT = F.CODNAT
					AND T.CODTIPTIT = F.CODTIPTIT ";
		
		if ($codparc <> '' && !$cb_agruparmatriz) {
			$sql .= " AND F.CODPARC = $codparc";
			
			// Parceiro
			$sql1 = "SELECT P.NOMEPARC, P.CGC_CPF, P.IDENTINSCESTAD FROM TGFPAR P WHERE P.CODPARC = $codparc";
			$bdsql1 = new BDORACLE();
			$bdsql1->desconecta();
			$bdsql1->conecta($usubanco);
			$bdsql1->execute_query($sql1,$bdsql1->conexao);
			while ($dt1 = $bdsql1->result_query()) {
				$nomeparc = $dt1['NOMEPARC'];
				$cgc_cpf = $dt['CGC_CPF'];
				$identinscestad = $dt['IDENTINSCESTAD'];
			}
		}
		if ($codparc <> '' && $cb_agruparmatriz) $sql .= " AND (P.CODPARCMATRIZ = $codparc OR F.CODPARC = $codparc)";
		if ($cp_nomeparc <> '')	$sql .= " AND UPPER(P.NOMEPARC) LIKE UPPER('%$cp_nomeparc%') ";
		
		if ($codemp <> '') 			$sql .= " AND F.CODEMP = $codemp";
		if ($empresas <> '') 		$sql .= " AND F.CODEMP IN ($empresas)";
		if ($numnota <> '') 		$sql .= " AND F.NUMNOTA = $numnota";
		if ($cp_origem <> '') 		$sql .= " AND F.ORIGEM = '$cp_origem'";
		if ($cb_provisao <> '') 	$sql .= " AND F.PROVISAO = '$cb_provisao'";
		if ($autorizados <> '') 	$sql .= " AND F.AUTORIZADO = '$autorizados'";
		if ($codproj <> '') 		$sql .= " AND F.CODPROJ = $codproj";
		if ($cb_codnat <> '') 		$sql .= " AND F.CODNAT = $cb_codnat";
		if ($sl_codtiptit <> '') 	$sql .= " AND F.CODTIPTIT = '$sl_codtiptit'";
		if ($cb_codsituacao <> '') 	$sql .= " AND F.AD_CODSIT = '$cb_codsituacao'" ;
		if ($cb_codcencus <> '') 	$sql .= " AND F.CODCENCUS = $cb_codcencus";
		if ($cb_agendado <> '')     $sql .= " AND SUBSTR(UPPER(F.AD_AGENDADO),1,1) = '$cb_agendado'";
		if ($cb_conferido <> '')    $sql .= " AND SUBSTR(UPPER(F.AD_CONFERIDO),1,1) = '$cb_conferido'";
		
		if ($cb_conf_contab <> '')  {
			if ($cb_conf_contab == 'S') $sql .= " AND SUBSTR(UPPER(F.AD_CONF_CONTAB),1,1) = 'S'";
			else $sql .= " AND (SUBSTR(UPPER(F.AD_CONF_CONTAB),1,1) = 'N' OR F.AD_CONF_CONTAB IS NULL)";
		}
		
		if ($data1 <> '' && $data2 <> '') $sql .= " AND F.DTVENC BETWEEN '$data1' AND '$data2'";
		if ($dataneg1 <> '' && $dataneg2 <> '') $sql .= " AND F.DTNEG BETWEEN '$dataneg1' AND '$dataneg2'";
		if ($dataentsai1 <> '' && $dataentsai1 <> '') $sql .= " AND DECODE(F.DTENTSAI,'',F.DTNEG,F.DTENTSAI) BETWEEN '$dataentsai1' AND '$dataentsai2'";
		if ($dataalter1 <> '' && $dataalter2 <> '') $sql .= " AND TRUNC(F.DTALTER) BETWEEN '$dataalter1' AND '$dataalter2'";
		
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
		
		if ($cb_entcontab == 'S') $sql .= " AND SUBSTR(UPPER(F.AD_ENTCONTAB),1,1) = '$cb_entcontab'";
		else if ($cb_entcontab <> '') $sql .= " AND (SUBSTR(UPPER(F.AD_ENTCONTAB),1,1) = '$cb_entcontab' OR F.AD_ENTCONTAB IS NULL)";
		
		if ($grupo == 'Pes') {
			// Projeto 100000000-Folha de Pagamento
			// Parceiro 573-Salarios, 4693-POLICARD, 214 - PORTO SEGURO SEGUROS, 4611 - SESI DR/DF, 883 - SETRANSP
			// 1375 - SINTRALGO-GO
			// CF 10103-RH Recursos Humanos
			$sql .= " AND (F.CODUSU = $codusuario OR F.CODPROJ = '100000000' OR F.CODCENCUS = '10103' OR F.CODPARC IN (573,4693,214,4611,883,1375)) ";
		}
		
		if ($grupo == 'GerCom') {
			$sql .= " AND (F.CODUSU = $codusuario OR F.CODCENCUS = '30603' OR F.CODCENCUS = '30501' OR F.CODUSU IN (530,288,616,1,60,516,749,492)) ";
		}
		
		if ($cb_agruparmatriz) {
			$sql .= " GROUP BY 	F.NUFIN, N.CODNAT, N.DESCRNAT, F.CODEMP, F.PROVISAO, F.NUFIN, P.CODPARCMATRIZ,
								F.NUNOTA, F.NUMNOTA, F.CODNAT, F.CODTIPTIT, T.DESCRTIPTIT,
								F.DESDOBRAMENTO, F.DTNEG, F.DTVENC, F.HISTORICO, F.AUTORIZADO, F.VLRDESDOB,
								F.AD_CONFERIDO, F.AD_AGENDADO, F.AD_CONF_CONTAB, F.AD_ENTCONTAB, F.ORIGEM, U3.NOMEUSU, 
								U3.EMAIL, F.CODUSU, F.AD_NUMPEDIDO, P.CLIENTE, P.SELECIONADO, P.CGC_CPF, P.IDENTINSCESTAD,
								N.INCRESULT, N.AD_CONSUMO, N.AD_UNICA, N.AD_FOLHA, N.AD_SEMMETA
					  ORDER BY P.CODPARCMATRIZ, F.CODEMP, F.NUMNOTA";
		} else {
			$sql .= " ORDER BY P.NOMEPARC, F.CODEMP, F.NUMNOTA";
		}
		
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
				$vetor[$cont]['codemp'] = $dt['CODEMP'];
				$vetor[$cont]['numnota'] = $dt['NUMNOTA'];
			}
			
			$vetor[$cont]['parceiro'] = $dt['CODPARC'];
			$vetor[$cont]['nomeparc'] = $dt['NOMEPARC'];
			$vetor[$cont]['cgc_cpf'] = $dt['CGC_CPF'];
			$vetor[$cont]['identinscestad'] = $dt['IDENTINSCESTAD'];
			$vetor[$cont]['nufin'] = $dt['NUFIN'];
			$vetor[$cont]['nunota'] = $dt['NUNOTA'];
			$vetor[$cont]['desdobramento'] = $dt['DESDOBRAMENTO'];
			$vetor[$cont]['dtneg'] = $dt['DTNEG'];
			$vetor[$cont]['dtvenc'] = $dt['DTVENC'];
			$vetor[$cont]['provisao'] = $dt['PROVISAO'];
			$vetor[$cont]['codnat'] = $dt['CODNAT'];
			$vetor[$cont]['natureza'] = $dt['DESCRNAT'];
			$vetor[$cont]['codtipotitulo'] = $dt['CODTIPTIT'];
			$vetor[$cont]['tipotitulo'] = $dt['DESCRTIPTIT'];
			$vetor[$cont]['autorizado'] = $dt['AUTORIZADO'];
			$vetor[$cont]['historico'] = $dt['HISTORICO'];
			$vetor[$cont]['nomeusuario_fin'] = $dt['USUARIO_FIN'];
			$vetor[$cont]['nomeusuario_cab'] = $dt['USUARIO_CAB'];
			$vetor[$cont]['codusucab'] = $dt['CODUSU_CAB'];
			$vetor[$cont]['origem'] = $dt['ORIGEM'];
			$vetor[$cont]['ad_conferido'] = $dt['AD_CONFERIDO'];
			$vetor[$cont]['ad_agendado'] = $dt['AD_AGENDADO'];
			$vetor[$cont]['ad_conf_contab'] = $dt['AD_CONF_CONTAB'];
			$vetor[$cont]['ad_entcontab'] = $dt['AD_ENTCONTAB'];
			$vetor[$cont]['codusufin'] = $dt['CODUSU'];
			$vetor[$cont]['emailsolicitante'] = $dt['EMAIL'];
			$vetor[$cont]['valor'] = $dt['VALOR'];
			$vetor[$cont]['cliente'] = $dt['CLIENTE'];
			$vetor[$cont]['selecionado'] = $dt['SELECIONADO'];
			$vetor[$cont]['ad_numpedido'] = $dt['AD_NUMPEDIDO'];
			$vetor[$cont]['descrcencus'] = $dt['DESCRCENCUS'];
			$vetor[$cont]['natvalidada'] = $dt['INCRESULT'];
			$vetor[$cont]['natconsumo'] = $dt['AD_CONSUMO'];
			$vetor[$cont]['natunica'] = $dt['AD_UNICA'];
			$vetor[$cont]['natfolha'] = $dt['AD_FOLHA'];
			$vetor[$cont]['natsemmeta'] = $dt['AD_SEMMETA'];
			
			$dt['EMAIL'] = '';			
			$dt['HISTORICO'] = $dt['DESDOBRAMENTO'] = '';
			$dt['USUARIO_FIN'] = $dt['USUARIO_CAB'] = '';
			$dt['AD_CONFERIDO'] = $dt['AUTORIZADO'] = '';
			$dt['ORIGEM'] = $dt['NUNOTA'] = $dt['SELECIONADO'] = $dt['AD_NUMPEDIDO'] = '';
			$dt['AD_CONF_CONTAB'] = $dt['NUMNOTA'] = $dt['AD_AGENDADO'] = $dt['AD_ENTCONTAB'] = '';
			$dt['INCRESULT'] = $dt['AD_CONSUMO'] = $dt['AD_UNICA'] = $dt['AD_FOLHA'] = $dt['AD_SEMMETA'] = '';
		}
		
		// Buscar base EX
		if ($ck_baseEX && $_SESSION["nomeservidor"] <> 'SRVEX' && ($codemp == '' || $codemp > 500)) {
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
					$vetor[$cont]['codemp'] = $dt['CODEMP'];
					$vetor[$cont]['numnota'] = $dt['NUMNOTA'];
				}
				
				$vetor[$cont]['parceiro'] = $dt['CODPARC'];
				$vetor[$cont]['nomeparc'] = $dt['NOMEPARC'];
				$vetor[$cont]['cgc_cpf'] = $dt['CGC_CPF'];
				$vetor[$cont]['identinscestad'] = $dt['IDENTINSCESTAD'];
				$vetor[$cont]['nufin'] = $dt['NUFIN'];
				$vetor[$cont]['nunota'] = $dt['NUNOTA'];
				$vetor[$cont]['desdobramento'] = $dt['DESDOBRAMENTO'];
				$vetor[$cont]['dtneg'] = $dt['DTNEG'];
				$vetor[$cont]['dtvenc'] = $dt['DTVENC'];
				$vetor[$cont]['provisao'] = $dt['PROVISAO'];
				$vetor[$cont]['codnat'] = $dt['CODNAT'];
				$vetor[$cont]['codtipotitulo'] = $dt['CODTIPTIT'];
				$vetor[$cont]['tipotitulo'] = $dt['DESCRTIPTIT'];
				$vetor[$cont]['natureza'] = $dt['DESCRNAT'];
				$vetor[$cont]['autorizado'] = $dt['AUTORIZADO'];
				$vetor[$cont]['historico'] = $dt['HISTORICO'];
				$vetor[$cont]['nomeusuario_fin'] = $dt['USUARIO_FIN'];
				$vetor[$cont]['nomeusuario_cab'] = $dt['USUARIO_CAB'];
				$vetor[$cont]['codusucab'] = $dt['CODUSU_CAB'];
				$vetor[$cont]['origem'] = $dt['ORIGEM'];
				$vetor[$cont]['ad_conferido'] = $dt['AD_CONFERIDO'];
				$vetor[$cont]['ad_agendado'] = $dt['AD_AGENDADO'];
				$vetor[$cont]['ad_conf_contab'] = $dt['AD_CONF_CONTAB'];
				$vetor[$cont]['ad_entcontab'] = $dt['AD_ENTCONTAB'];
				$vetor[$cont]['codusufin'] = $dt['CODUSU'];
				$vetor[$cont]['emailsolicitante'] = $dt['EMAIL'];
				$vetor[$cont]['valor'] = $dt['VALOR'];
				$vetor[$cont]['cliente'] = $dt['CLIENTE'];
				$vetor[$cont]['selecionado'] = $dt['SELECIONADO'];
				$vetor[$cont]['ad_numpedido'] = $dt['AD_NUMPEDIDO'];
				$vetor[$cont]['descrcencus'] = $dt['DESCRCENCUS'];
				$vetor[$cont]['natvalidada'] = $dt['INCRESULT'];
				$vetor[$cont]['natconsumo'] = $dt['AD_CONSUMO'];
				$vetor[$cont]['natunica'] = $dt['AD_UNICA'];
				$vetor[$cont]['natfolha'] = $dt['AD_FOLHA'];
				$vetor[$cont]['natsemmeta'] = $dt['AD_SEMMETA'];
				
				$dt['EMAIL'] = '';			
				$dt['HISTORICO'] = $dt['DESDOBRAMENTO'] = '';
				$dt['USUARIO_FIN'] = $dt['USUARIO_CAB'] = '';
				$dt['AD_CONFERIDO'] = $dt['AUTORIZADO'] = '';
				$dt['ORIGEM'] = $dt['NUNOTA'] = $dt['SELECIONADO'] = $dt['AD_NUMPEDIDO'] = '';
				$dt['AD_CONF_CONTAB'] = $dt['NUMNOTA'] = $dt['AD_AGENDADO'] = $dt['AD_ENTCONTAB'] = '';
				$dt['INCRESULT'] = $dt['AD_CONSUMO'] = $dt['AD_UNICA'] = $dt['AD_FOLHA'] = $dt['AD_SEMMETA'] = '';
			}
		}
		
		// Ordenando o array
		if ($vetor <> '') sort($vetor);
	}
	
	// Fechamento de Financeiro
	// Usuário.: Lauro
	// Solicit.: Gleidson
	// Data....: 19/12/11
	
	if ($btnfechar && $data2 <> '') {
		// Alterando Fechamento
		$sqlInsert = "UPDATE TSIPAR SET DATA = '$data2' WHERE CHAVE = 'DATAFECHAFIN' AND DATA < '$data2'";
		$bdsqlInsert = new BDORACLE();
		$bdsqlInsert->desconecta();
		$bdsqlInsert->conecta($usubanco);
		$bdsqlInsert->execute_query($sqlInsert,$bdsqlInsert->conexao);
		
		if ($ck_baseEX && $_SESSION["nomeservidor"] <> 'SRVEX') {
			$bdsqlInsertEX = new BDORACLE();
			$bdsqlInsertEX->desconecta();
			$bdsqlInsertEX->conectaEX($usubanco);
			$bdsqlInsertEX->execute_query($sqlInsert,$bdsqlInsertEX->conexao);
		}
		
		// Verificando se Alterou
		$datafechafin = 'N';
		$sql1 = "SELECT DATA FROM TSIPAR WHERE CHAVE = 'DATAFECHAFIN' AND DATA = '$data2'";
		$bdsql1 = new BDORACLE();
		$bdsql1->desconecta();
		$bdsql1->conecta($usubanco);
		$bdsql1->execute_query($sql1,$bdsql1->conexao);
		while ($dt1 = $bdsql1->result_query()) {
			$datafechafin = 'S';
			echo "O parâmetro de Data do Fechamento Financeiro foi alterado para $data2 !";
		}
		
		// Validando alteração Data Fechamento
		if ($datafechafin == 'N') {
			echo "Atenção! O parâmetro de Data do Fechamento Financeiro não foi alterado!";
		}
		
		$texto = "<html><body><meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>";
		$texto .= "<table border=1 cellspacing=1 cellpadding=2 align='center' bordercolor='#FFFFFF' bgcolor='#FFFFFF'>";
		$texto .= "<tr><td bgcolor='#99AACC' align='center' colspan='2'><font size='2' face='Arial'><b>Fechamento do Financeiro</b></font></td></tr>";
		$texto .= "<tr><td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'><b>Data</b></font></td>";
		$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>$data2</font></td></tr>";
		$texto .= "<tr><td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'><b>Usuário</b></font></td>";
		$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>$usuario</font></td></tr>";
		$texto .= "</table></body></html>";
		
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
        $mail->Subject = "Fechamento do Financeiro"; // Define o Assunto
        $mail->Body = $texto; // Corpo da mensagem em formato HTML
		
		$mail->AddAddress($ck_email,""); // Email e Nome do destinatário		
		
		$para[0] = 'financeiro@aguanativa.com.br';
		$para[1] = 'ronaldo@aguanativa.com.br';
		
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
?>
<style>
<!--
body {background-color:white}
A {text-decoration: none; color:#003366}
a:hover {color:red;}
-->
</style>
</head>

<body bgcolor="#FFFFFF" text="#000000" onLoad="form1.codparc.focus()" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<?php 
	$paginaorigem = end(explode("/", $_SERVER['PHP_SELF']));
	include_once("menu.php");
?>
<br>
<table border="0" cellspacing="1" cellpadding="0" align="center" bordercolor="#FFFFFF" bgcolor="#CCCCCC" width="800">
  <tr>
	<td height="20" width="1%"><a href="duppagar.php?PHPSESSID=<?php echo $PHPSESSID;?>&nocache=true"><img src="img/fundotitulovoltar.jpg" border="0" alt="Voltar a Tela"></a></td>
	<td height="20" bgcolor="#E5EEEE" bordercolor="#E5EEEE" width="97%">
		<div align="center"> <font size="2" face="Arial" color="#000000"><b>Duplicatas a Pagar</b></font></div>
	</td>
	<td height="20" width="1%" align="right"><img src="img/ajuda.jpg" border="0" alt="Ajuda" style="cursor: hand" onClick="window.open('ajuda.php?PHPSESSID=<?php echo $PHPSESSID;?>&codajuda=33', 'JANELA', 'left = 50, top = 50, height = 550, width = 770, scrollbars=yes, titlebar=yes, menubar=yes, location=yes, toolbar=no, status=yes, resizable=yes')"></td>
	<td height="20" width="1%"><a href='home.php?PHPSESSID=<?php echo $PHPSESSID;?>'><img src="img/fundotitulofechar2.jpg" border="0" alt="Fechar"></a></td>
  </tr>   
  <tr> 
    <td bgcolor="#FFFFFF" bordercolor="#FFFFFF" valign="middle" colspan="4"> <br>
	<form name="form1" method="post" action="duppagar.php?PHPSESSID=<?php echo $PHPSESSID;?>&btnbuscar=true">
        <table border="1" cellspacing="1" cellpadding="1" align="center" bordercolor="#FFFFFF" bgcolor="#000000" width="700">
          <tr> 
            <td bgcolor="#99AACC" bordercolor="#99AACC" width="130"><b><font face="Arial" size="2"><a href="parceiros.php?PHPSESSID=<?php echo $PHPSESSID;?>&voltar=duppagar.php?PHPSESSID=<?php echo $PHPSESSID;?>">Parceiro</a></font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE" width="570" colspan="3"> 
              <input type="text" name="codparc" size="5" maxlength="5" value="<?php echo $codparc;?>">
              &nbsp;<font face="Arial" size="2"><?php echo substr($nomeparc,0,34);?></font>
			  <script type="text/javascript">document.getElementById('codparc').focus();</script>
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
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Data&nbsp;Neg.</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE" colspan="3"> 
              <input type="text" name="dataneg1" size="10" maxlength="10" value="<?php echo $dataneg1;?>">
              <font face="Arial" size="2">a</font>
              <input type="text" name="dataneg2" size="10" maxlength="10" value="<?php echo $dataneg2;?>">
            </td>
          </tr>
          <tr>
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Data&nbsp;Ent Sai</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE" colspan="3">
              <input type="text" name="dataentsai1" size="10" maxlength="10" value="<?php echo $dataentsai1;?>">
              <font face="Arial" size="2">a</font>
              <input type="text" name="dataentsai2" size="10" maxlength="10" value="<?php echo $dataentsai2;?>">
            </td>
          </tr>
          <tr>
            <td bgcolor="#99AACC" bordercolor="#99AACC" width="110"><b><font face="Arial" size="2">Data&nbsp;Alter</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE" width="270">
              <input type="text" name="dataalter1" size="10" maxlength="10" value="<?php echo $dataalter1;?>">
              <font face="Arial" size="2">a</font>
              <input type="text" name="dataalter2" size="10" maxlength="10" value="<?php echo $dataalter2;?>">
            </td>
			<td bgcolor="#99AACC" bordercolor="#99AACC" width="110" title="Nota Fiscal"><b><font face="Arial" size="2">NF</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE" width="130">
              <input type="text" name="numnota" size="6" value="<?php echo $numnota;?>">
            </td>
          </tr>
          <tr>
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Data&nbsp;Venc</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE">
              <input type="text" name="data1" size="10" maxlength="10" value="<?php echo $data1;?>">
              <font face="Arial" size="2">a</font>
              <input type="text" name="data2" size="10" maxlength="10" value="<?php echo $data2;?>">
            </td>
            <td bgcolor="#99AACC" bordercolor="#99AACC" title="Conferido pelo Financeiro"><b><font face="Arial" size="2">Conf. Finan.</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE">
              <select name="cb_conferido">
			    <option></option>
				<option value='S' <?php if ($cb_conferido == 'S') echo 'selected';?>>Sim</option>
		        <option value='N' <?php if ($cb_conferido == 'N') echo 'selected';?>>Não</option>
			  </select>
            </td>
          </tr>
          <tr> 
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Empresa</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE">
              <input type="text" name="codemp" size="3" maxlength="5" value="<?php echo $codemp;?>">
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
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Origem</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE"> 
              <select name="cp_origem">
			  	<option></option>
                <option value='E' <?php if ($cp_origem == 'E') echo 'selected'?>>Estoque</option>
                <option value='F' <?php if ($cp_origem == 'F') echo 'selected'?>>Financeiro</option>
              </select>			
            </td>
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Provis&atilde;o</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE"> 
              <select name="cb_provisao" onchange="form1.cb_agendado.value = ''; form1.cb_conferido.value = '';">
			    <option></option>
                <option value='S' <?php if ($cb_provisao == 'S') echo 'selected'?>>Sim</option>
				<option value='N' <?php if ($cb_provisao == 'N') echo 'selected'?>>Não</option>
 			  </select>
            </td>
          </tr>		  
          <tr> 
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Autorizado</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE"> 
              <select name="autorizados">
			    <option></option>
                <option value='S' <?php if ($autorizados == 'S') echo 'selected'?>>Sim</option>
			    <option value='N' <?php if ($autorizados == 'N') echo 'selected'?>>Não</option>
			  </select>
            </td>
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Agendado</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE">
              <select name="cb_agendado">
			    <option></option>
			    <option value='S' <?php if ($cb_agendado == 'S') echo 'selected'?>>Sim</option>
                <option value='N' <?php if ($cb_agendado == 'N') echo 'selected'?>>Não</option>
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
			<td bgcolor="#99ACCC" bordercolor="#99ACCC"><b><font face="Arial" size="2">Matriz</font></b></td>
			<td bordercolor="#EEEEEE" bgcolor="#EEEEEE">
			  <input type="checkbox" name="cb_agruparmatriz" value="checkbox" <?php if ($cb_agruparmatriz) echo 'checked';?>>
			</td>			
          </tr>
		  <tr>
            <td bgcolor="#99AACC" bordercolor="#99AACC">
			  <font face="Arial" size="2"><b>Situação</b></font>
			</td>
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
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Anexo</font></b></td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE">
              <select name="cb_anexo">
				<option></option>
		        <option value='SIM' <?php if ($cb_anexo == 'SIM') echo 'selected';?>>Sim</option>
		        <option value='NAO' <?php if ($cb_anexo == 'NAO') echo 'selected';?>>Não</option>
                </select>
            </td>
          </tr>
          <tr> 
            <td bgcolor="#99AACC" bordercolor="#99AACC"><b><font face="Arial" size="2">Centro Resultado</font></b></td>
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
             <td bgcolor="#EEEEEE" bordercolor="#EEEEEE" colspan="3">
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
          </tr>
	  
			<tr>
            <td bgcolor="#99AACC" bordercolor="#99AACC">
              <b><font face="Arial" size="2">Tipo Título</font></b>
            </td>
            <td bgcolor="#EEEEEE" bordercolor="#EEEEEE" colspan="3">
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
            </td>
          </tr>
		  

		  <tr>
            <td colspan="4" valign="middle" bordercolor="#DDDDDD" bgcolor="#DDDDDD" align="center">
			  <input type="submit" name="btnbuscar" value="Buscar">
<?php if ($grupo == 'Dir') { ?>
			  &nbsp;&nbsp;&nbsp;
			  <input type="button" name="btnfechar" value="Fechar Financeiro" onClick="window.location = 'duppagar.php?PHPSESSID=<?php echo $PHPSESSID;?>&btnfechar=true&btnbuscar=true&todos=N&autorizados=<?php echo $autorizados;?>&cb_provisao=<?php echo $cb_provisao;?>&data1=<?php echo $data1;?>&data2=<?php echo $data2;?>&dataneg1=<?php echo $dataneg1;?>&dataneg2=<?php echo $dataneg2;?>&dataentsai1=<?php echo $dataentsai1;?>&dataentsai2=<?php echo $dataentsai2;?>&dataalter1=<?php echo $dataalter1;?>&dataalter2=<?php echo $dataalter2;?>&codparc=<?php echo $codparc;?>&cp_nomeparc=<?php echo $cp_nomeparc;?>&codemp=<?php echo $codemp;?>&empresas=<?php echo $empresas;?>&cb_agruparmatriz=<?php echo $cb_agruparmatriz;?>&cb_anexo=<?php echo $cb_anexo;?>&cb_codnat=<?php echo $cb_codnat;?>&cb_codcencus=<?php echo $cb_codcencus;?>&codproj=<?php echo $codproj;?>&cb_codsituacao=<?php echo $cb_codsituacao;?>&sl_codtiptit=<?php echo $sl_codtiptit;?>&cb_agendado=<?php echo $cb_agendado;?>&cb_entcontab=<?php echo $cb_entcontab;?>&cb_conferido=<?php echo $cb_conferido;?>&cb_conf_contab=<?php echo $cb_conf_contab;?>&cp_origem=<?php echo $cp_origem;?>&cp_codusu=<?php echo $cp_codusu;?>&cb_natvalidada=<?php echo $cb_natvalidada;?>&cb_natconsumo=<?php echo $cb_natconsumo;?>&cb_natunica=<?php echo $cb_natunica;?>&cb_natfolha=<?php echo $cb_natfolha;?>&cb_natsemmeta=<?php echo $cb_natsemmeta;?>&cb_natoutras=<?php echo $cb_natoutras;?>'">
<?php } ?>
            </td>
          </tr>
        </table>
	  </form>
	  
<?php
	if ($btnbuscar) {
?>
      <form name="form2" method="post" action="duppagar.php?PHPSESSID=<?php echo $PHPSESSID;?>&alterar=true&btnbuscar=true&todos=N&autorizados=<?php echo $autorizados;?>&cb_provisao=<?php echo $cb_provisao;?>&data1=<?php echo $data1;?>&data2=<?php echo $data2;?>&dataneg1=<?php echo $dataneg1;?>&dataneg2=<?php echo $dataneg2;?>&dataentsai1=<?php echo $dataentsai1;?>&dataentsai2=<?php echo $dataentsai2;?>&dataalter1=<?php echo $dataalter1;?>&dataalter2=<?php echo $dataalter2;?>&codparc=<?php echo $codparc;?>&cp_nomeparc=<?php echo $cp_nomeparc;?>&codemp=<?php echo $codemp;?>&empresas=<?php echo $empresas;?>&cb_agruparmatriz=<?php echo $cb_agruparmatriz;?>&cb_anexo=<?php echo $cb_anexo;?>&cb_codnat=<?php echo $cb_codnat;?>&cb_codcencus=<?php echo $cb_codcencus;?>&codproj=<?php echo $codproj;?>&cb_codsituacao=<?php echo $cb_codsituacao;?>&sl_codtiptit=<?php echo $sl_codtiptit;?>&cb_agendado=<?php echo $cb_agendado;?>&cb_entcontab=<?php echo $cb_entcontab;?>&cb_conferido=<?php echo $cb_conferido;?>&cb_conf_contab=<?php echo $cb_conf_contab;?>&cp_origem=<?php echo $cp_origem;?>&cp_codusu=<?php echo $cp_codusu;?>&numnota=<?php echo $numnota;?>&cb_natvalidada=<?php echo $cb_natvalidada;?>&cb_natconsumo=<?php echo $cb_natconsumo;?>&cb_natunica=<?php echo $cb_natunica;?>&cb_natfolha=<?php echo $cb_natfolha;?>&cb_natsemmeta=<?php echo $cb_natsemmeta;?>&cb_natoutras=<?php echo $cb_natoutras;?>">
        <table border="1" cellspacing="1" cellpadding="1" align="center" bordercolor="#FFFFFF" bgcolor="#CCCCCC" width="780">
<?php if ($ad_autoriza1 == 'SIM') { ?>
          <tr bgcolor="#BBBBBB">
            <td bordercolor="#99ACCC" bgcolor="#99ACCC" colspan="15"><b><font face="Arial" size="2"> 
              Marcar Todos
              <input type="checkbox" name="todos" value="N" <?php if ($todos == 'S') echo 'checked'; if ($autorizados == 'S' && $autorizados2 == 'S') echo 'disabled';?> onClick="return marcartodos(this);">
              </font></b>
			</td>
          </tr>
<?php } ?>
          <tr bgcolor="#BBBBBB"> 
            <td bordercolor="#FAEFCC" bgcolor="#FAEFCC">
              <div align="center"><b><font face="Arial" size="2">Auto</font></b></div>
            </td>
            <td bordercolor="#FAEFCC" bgcolor="#FAEFCC" > 
              <div align="center"><b><font face="Arial" size="2">Emp</font></b></div>
            </td>			
            <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
              <div align="center"><b><font face="Arial" size="2">NF</font></b></div>
            </td>
			<td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
              <div align="center"><b><font face="Arial" size="2">NºÚnico</font></b></div>
			</td>
            <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
              <div align="center"><b><font face="Arial" size="2">P</font></b></div>
            </td>
			<td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
              <div align="center"><b><font face="Arial" size="2">Natureza&nbsp;Fin</font></b></div>
            </td>
            <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
              <div align="center"><b><font face="Arial" size="2">Emissão</font></b></div>
            </td>
            <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
              <div align="center"><b><font face="Arial" size="2">Prorrog</font></b></div>
            </td>
			<td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
			  <div align="center"><b><font face="Arial" size="2">Tipo&nbsp;Título</font></b></div>
            </td>	
            <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
              <div align="center"><b><font face="Arial" size="2">Usuário</font></b></div>
            </td>
            <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
              <div align="center"><b><font face="Arial" size="2">Liberador</font></b></div>
            </td>			
            <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
              <div align="center"><b><font face="Arial" size="2">Histórico</font></b></div>
            </td>
            <td bordercolor="#FAEFCC" bgcolor="#FAEFCC" title="Provisão"> 
              <div align="center"><b><font face="Arial" size="2">Prov</font></b></div>
            </td>		
            <td bordercolor="#FAEFCC" bgcolor="#FAEFCC" title="Origem"> 
              <div align="center"><b><font face="Arial" size="2">Orig</font></b></div>
            </td>
			 <td bordercolor="#FAEFCC" bgcolor="#FAEFCC" title="Agendado pelo Financeiro"> 
              <div align="center"><b><font face="Arial" size="2">Age</font></b></div>
            </td>
            <td bordercolor="#FAEFCC" bgcolor="#FAEFCC" title="Conferido pelo Financeiro"> 
              <div align="center"><b><font face="Arial" size="2">Conf</font></b></div>
            </td>
			<td bordercolor="#FAEFCC" bgcolor="#FAEFCC" title="Entregue para Contabilidade"> 
              <div align="center"><b><font face="Arial" size="2">Ent</font></b></div>
            </td>
			<td bordercolor="#FAEFCC" bgcolor="#FAEFCC" title="Conferido pela Contabilidade">
              <div align="center"><b><font face="Arial" size="2">Ctb</font></b></div>
            </td>
            <td bordercolor="#FAEFCC" bgcolor="#FAEFCC" title="Arquivos Anexados"> 
              <div align="center"><b><font face="Arial" size="2"><img src="img/anexo2.jpg" border="0" width="15"></font></b></div>
            </td>
            <td bordercolor="#FAEFCC" bgcolor="#FAEFCC" title="Arquivos Anexados Pagamento"> 
              <div align="center"><b><font face="Arial" size="2"><img src="img/anexo2.jpg" border="0" width="15"></font></b></div>
            </td>
            <td bordercolor="#FAEFCC" bgcolor="#FAEFCC"> 
              <div align="center"><b><font face="Arial" size="2">Valor</font></b></div>
            </td>
          </tr>
<?php
	
	$bdsql1 = new BDORACLE();
	$bdsql1->desconecta();
	$bdsql1->conecta($usubanco);
	
	$parceiroant = $parceiroant2 = $parceiro = $dtvenc = $historico = $tipotitulo = '';
	$valor = $soma = $somaParc = $soma2 = $somaParc2 = $contAlt = $contEntctb = 0;
	$dt = '';
	$podeprorrogar = $podeprorrogartitulo = false;
	$texto = "<html><body>";
	
	if ($cont > 0) {
		for ($cont=0; $cont<sizeof($vetor); $cont++) {
			$visualizar = true;
			$historico = $autorizado = $autorizadoant = '';
			$nomesolicitante = $nomeusuautorizou = $dhlib = '';
			$codsolicitante = $tipotitulo = '';
			$alterado = $alteradoAge = $alteradofin = $alteradoctb = $alteradoentctb = $podeprorrogartitulo = false;
			$exigirpedido = false;
			
			$parceiro = $vetor[$cont]['parceiro'];
			$nomeparc = $vetor[$cont]['nomeparc'];
			$cgc_cpf = $vetor[$cont]['cgc_cpf'];
			$identinscestad = $vetor[$cont]['identinscestad'];
			
			// Formatar CNPJ
			$cgc_cpf = substr($cgc_cpf, 0, 2) . '.' . substr($cgc_cpf, 2, 3) . '.' . substr($cgc_cpf, 5, 3) . '/' . substr($cgc_cpf, 8, 4) . '-' . substr($cgc_cpf, 12, 2);
			
			if ($parceiroant <> $parceiro) {
				if ($somaParc > 0) {
					echo "<tr><td bgcolor='#FFFFFF' align='center' colspan='20'><font size='2' face='Arial' color='#003366'><b>Total do Parceiro</b></font></td>";
					echo "<td bgcolor='#FFFFFF' align='right'><font size='2' face='Arial' color='#003366'><b>".number_format(str_replace(',','.',$somaParc), 2, ',', '.')."</b></font></td></tr>";
					$somaParc = 0;
				}
			}
			
			$codemp = $vetor[$cont]['codemp'];
			$nufin = $vetor[$cont]['nufin'];
			$nunota = $vetor[$cont]['nunota'];
			$numnota = $vetor[$cont]['numnota'];
			$desdobramento = $vetor[$cont]['desdobramento'];
			$dtneg = $vetor[$cont]['dtneg'];
			$dtvenc = $vetor[$cont]['dtvenc'];
			$provisao = $vetor[$cont]['provisao'];
			$codnat = $vetor[$cont]['codnat'];
			$natureza = $vetor[$cont]['natureza'];
			$codtipotitulo = $vetor[$cont]['codtipotitulo'];
			$tipotitulo = $vetor[$cont]['tipotitulo'];
			$autorizado = $vetor[$cont]['autorizado'];
			$historico = $vetor[$cont]['historico'];
			$nomeusuario_fin = $vetor[$cont]['nomeusuario_fin'];
			$nomeusuario_cab = $vetor[$cont]['nomeusuario_cab'];
			$codusucab = $vetor[$cont]['codusucab'];
			$origem = $vetor[$cont]['origem'];
			$ad_conferido = $vetor[$cont]['ad_conferido'];
			$ad_agendado = $vetor[$cont]['ad_agendado'];
			$ad_conf_contab = $vetor[$cont]['ad_conf_contab'];
			$ad_entcontab = $vetor[$cont]['ad_entcontab'];
			$codusufin = $vetor[$cont]['codusufin'];
			$emailsolicitante = $vetor[$cont]['emailsolicitante'];
			$cliente = $vetor[$cont]['cliente'];
			$selecionado = $vetor[$cont]['selecionado'];
			$ad_numpedido = $vetor[$cont]['ad_numpedido'];
			$natvalidada = $vetor[$cont]['natvalidada'];
			$natconsumo = $vetor[$cont]['natconsumo'];
			$natunica = $vetor[$cont]['natunica'];
			$natfolha = $vetor[$cont]['natfolha'];
			$natsemmeta = $vetor[$cont]['natsemmeta'];
			
			// Se origem financeiro e sem número do pedido e natureza diferente de 
			// 4180000-Depreciação Vasilhames NATIVA
			// 1021900-(-) Custo da Venda do Imobilizado
			// 4220000-Despesas de Depreciação do Imobilizado
			// 3061300-Provisão para devedores duvidosos 1,5% (Maryelle/Lauro - 20/09/19 - E-mail)
			if ($origem == 'F' && $ad_numpedido == '' && $codnat <> 4180000 && $codnat <> 1021900 && $codnat <> 4220000 && $codnat <> 3061300) {
				$exigirpedido = true;
				
				if ($parceiro == 99 && $codemp == 1)     $exigirpedido = false;		// SAUDE
				if ($parceiro == 99 && $codemp == 501)   $exigirpedido = false;		// SAUDE
				if ($parceiro == 1151 && $codemp == 3)   $exigirpedido = false;		// VEREDAS
				if ($parceiro == 1151 && $codemp == 503) $exigirpedido = false;		// VEREDAS
				if ($parceiro == 1267 && $codemp == 11)  $exigirpedido = false;		// BELO VALLE
				if ($parceiro == 1267 && $codemp == 511) $exigirpedido = false;		// BELO VALLE
			}

			// Liberar estas naturezas para o Jefferson (Supervisor Financeiro) autorizar (Lauro/Gleidson - 09/05/12)			
			// 3060500 - Tarifas Bancárias
			// 5090000 - ICMS Substituição Tributária - GNRE
			// 2060000 - Compra Sucata - Liberado para JEFFERSON - Gleidson/lauro 04/11/14
			
			if ($grupo == 'GerFin' && ($codnat == '3060500' || $codnat == '5090000')) {
				$podeautorizar = true;
			} else if ($grupo == 'GerFin' && $codnat == '2060000' && substr($ad_conferido,0,1) == "S") {
				$podeautorizar = true;
			} else {
				$podeautorizar = false;
			}
			
			if ($grupo == 'Dir') {
				$podeautorizar = true;
			}
			
			if ($nunota <> '') {
				// Buscando Liberação
				$sql1 = "SELECT U1.CODUSU, U1.NOMEUSU, U1.EMAIL, U2.NOMEUSU AS USUAUTORIZOU, L.DHLIB, L.REPROVADO, U1.AD_CODUSUSUP
						 FROM TSILIB L, TSIUSU U1, TSIUSU U2";
				if ($provisao <> 'S') $sql1 .= ", TGFVAR V";
				$sql1 .= " WHERE U1.CODUSU = L.CODUSUSOLICIT AND U2.CODUSU = L.CODUSULIB ";
				if ($provisao == 'S') $sql1 .= " AND L.NUCHAVE = $nunota ";
				else $sql1 .= " AND V.NUNOTA = $nunota
								AND L.NUCHAVE = V.NUNOTAORIG AND L.TABELA = 'TGFCAB' ";
				$sql1 .= " ORDER BY L.DHLIB";
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
					$dhlib = $dt1['DHLIB'];
					$reprovado = $dt1['REPROVADO'];
					$emailsolicitante = $dt1['EMAIL'];
					$codususupsolicitante = $dt1['AD_CODUSUSUP'];
					
					$dt1['CODUSU'] = $dt1['NOMEUSU'] = $dt1['USUAUTORIZOU'] = $dt1['DHLIB'] = $dt1['REPROVADO'] = '';
					$dt1['EMAIL'] = $dt1['AD_CODUSUSUP'] = '';
				}
				
				if ($codsolicitante == '') {
					// Buscando Liberação
					$sql1 = "SELECT U1.CODUSU, U1.NOMEUSU, U1.EMAIL, U2.NOMEUSU AS USUAUTORIZOU, L.DHLIB, L.REPROVADO
							 FROM TSILIB L, TSIUSU U1, TSIUSU U2, TGFVAR V
							 WHERE U1.CODUSU = L.CODUSUSOLICIT AND U2.CODUSU = L.CODUSULIB
							 AND V.NUNOTA = $nunota
							 AND L.NUCHAVE = V.NUNOTAORIG AND L.TABELA = 'TGFCAB'
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
						$dhlib = $dt1['DHLIB'];
						$reprovado = $dt1['REPROVADO'];
						$emailsolicitante = $dt1['EMAIL'];
						
						$dt1['CODUSU'] = $dt1['NOMEUSU'] = $dt1['USUAUTORIZOU'] = $dt1['DHLIB'] = $dt1['REPROVADO'] = '';
						$dt1['EMAIL'] = '';
					}
				}
				
				if ($codsolicitante == '') {
					// Buscando Liberação
					$sql1 = "SELECT U1.CODUSU, U1.NOMEUSU, U1.EMAIL, U2.NOMEUSU AS USUAUTORIZOU, L.DHLIB, L.REPROVADO
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
						$dhlib = $dt1['DHLIB'];
						$reprovado = $dt1['REPROVADO'];
						$emailsolicitante = $dt1['EMAIL'];
						
						$dt1['CODUSU'] = $dt1['NOMEUSU'] = $dt1['USUAUTORIZOU'] = $dt1['DHLIB'] = $dt1['REPROVADO'] = '';
						$dt1['EMAIL'] = '';
					}
				}
			}
			
			if ($ad_numpedido <> '' && $nomeusuautorizou == '') {
				// Buscando usuário quem autorizou o pedido
				$sql1 = "SELECT U2.NOMEUSU AS USUAUTORIZOU, L.DHLIB
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
					$dhlib = $dt1['DHLIB'];
					
					$dt1['USUAUTORIZOU'] = $dt1['DHLIB'] = '';
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
					
					if ($nomeusuautorizou == '') $nomeusuautorizou = 'Pedido '.$ad_numpedido.' não encontrado';
				}
			}
			
			if (($cp_codusu == '' && ($verapenasclientes <> 'S' || ($verapenasclientes == 'S' && (($cliente == 'S' && ($selecionado == 'A' || $selecionado == 'C')) || (($codsolicitante == '' && $codusucab <> '' && $codusucab == $codusuario) || $codusucab == $codusuario || $codusufin == $codusuario || ($codsolicitante <> '' && $codsolicitante == $codusuario)))))) || ($codsolicitante == '' && $codusucab <> '' && $codusucab == $cp_codusu) || $codusucab == $cp_codusu || $codusufin == $cp_codusu || ($codsolicitante <> '' && $codsolicitante == $cp_codusu)) {
				if ($parceiroant <> $parceiro) {
					$parceiroant = $parceiro;
					echo "<tr><td bgcolor='#EEEEEE' colspan='21'><font size='2' face='Arial' color='#003366'><b>$parceiro&nbsp;-&nbsp;$nomeparc</b>&nbsp; - CNPJ:&nbsp;$cgc_cpf</b>&nbsp; - IE:&nbsp;$identinscestad</b></font></td></tr>";
				}
				
				if ($nomesolicitante == '') {
					if ($nomeusuario_cab == '') {
						$nomesolicitante = $nomeusuario_fin;
						$codsolicitante = $codusufin;
					} else {
						$nomesolicitante = $nomeusuario_cab;
						$codsolicitante = $codusucab;

						// Buscando E-mail do usuário da CAB
						$sql1 = "SELECT EMAIL FROM TSIUSU WHERE CODUSU = '$codusucab'";
						$bdsql1 = new BDORACLE();
						$bdsql1->desconecta();
						$bdsql1->conecta($usubanco);
						$bdsql1->execute_query($sql1,$bdsql1->conexao);
						while ($dt1 = $bdsql1->result_query()) {
							$emailsolicitante = $dt1['EMAIL'];
						}
					}
				}
				
				$corusuautorizou = '#000000';
				if ($dhlib == '') $corusuautorizou = 'red';
				if ($reprovado == 'S') $corusuautorizou = 'orange';
				if ($tipotitulo == '<SEM TIPO DE TITULO>') $tipotitulo = '';
				
				if ($ad_agendado == '') $ad_agendado = 'N';
				if ($ad_conferido == '') $ad_conferido = 'N';
				if ($ad_entcontab == '') $ad_entcontab = 'N';
				if ($ad_conf_contab == '') $ad_conf_contab = 'N';
				
				$soma = $soma + str_replace(',','.',$vetor[$cont]['valor']);
				$somaParc = $somaParc + str_replace(',','.',$vetor[$cont]['valor']);
				$valor = number_format(str_replace(',','.',$vetor[$cont]['valor']), 2, ',', '.');
				
				if ($grupo == 'Dir' || $provisao == 'S' || (($grupo == 'Fin' || $grupo == 'GerFin') && ($provisao == 'N' || $codsolicitante == 509 || ($provisao == 'S' && $codsolicitante == $codusuario))) || (($grupo == 'Ctb' || $grupo == 'Sec' || $usuario == 'RAIANE' || $usuario == 'LETICIA') && $provisao == 'N')) {
					$podeprorrogar = true;
					$podeprorrogartitulo = true;
				}
				
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
					if ((substr($dt1['DESCRICAO'],0,4) == 'pgto'
						|| strstr(strtoupper($dt1['ARQUIVO']), 'PGTO') <> ''
						|| strstr(strtoupper($dt1['ARQUIVO']), 'PAGTO') <> ''
						|| strstr(strtoupper($dt1['ARQUIVO']), 'PAGAMENTO') <> '')
						&& strstr(strtoupper($dt1['ARQUIVO']), 'FOLHA PAGTO') == '') {
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
					
					if ($btnprorrogar && $campodata[$nufin] <> '' && $campodata[$nufin] <> $dtvenc) {
						// Prorrogando e alterando o usuário
						$sqlUpdate = "UPDATE TGFFIN SET DTVENC = '$campodata[$nufin]', CODUSU = '$codusuario', DTALTER = SYSDATE
									  WHERE NUFIN = $nufin AND RECDESP = -1 AND DHBAIXA IS NULL AND (AD_AGENDADO <> 'S' OR AD_AGENDADO IS NULL) ";						
						if ($codemp > 500) {
							$bdsqlInsertEX->execute_query($sqlUpdate,$bdsqlInsertEX->conexao);
						} else {
							$bdsqlInsert->execute_query($sqlUpdate,$bdsqlInsert->conexao);
						}
						
						$dtvenc = $campodata[$nufin];
					} else {
						if ($btnautorizar && $campo[$nufin] == 'S') {
							if ($campo[$nufin] == 'S' && ($ad_autoriza1 == 'SIM' || $podeautorizar)) {
								if ($autorizado <> 'S') {
									$contAlt++;
									$alterado = true;
									
									// Autorizando
									$sqlUpdate = "UPDATE TGFFIN SET AUTORIZADO = 'S', AD_CODUSUAUTO = '$codusuario'
												  WHERE NUFIN = $nufin AND RECDESP = -1 AND DHBAIXA IS NULL";
									if ($codemp > 500) {
										$bdsqlInsertEX->execute_query($sqlUpdate,$bdsqlInsertEX->conexao);
									} else {
										$bdsqlInsert->execute_query($sqlUpdate,$bdsqlInsert->conexao);
									}
									
									$autorizado = 'S';
									$historico .= " . Autorizado por $usuario.";
								}
							}
						} else if ($btndesautorizar) {
							if ($ad_autoriza1 == 'SIM' || $podeautorizar) {
								if ($autorizado == 'S') {
									$contAlt++;
									$alterado = true;
									
									// Desautorizando
									$sqlUpdate = "UPDATE TGFFIN SET AUTORIZADO = 'N'
												  WHERE NUFIN = $nufin AND RECDESP = -1 AND DHBAIXA IS NULL";
									if ($codemp > 500) {
										$bdsqlInsertEX->execute_query($sqlUpdate,$bdsqlInsertEX->conexao);
									} else {
										$bdsqlInsert->execute_query($sqlUpdate,$bdsqlInsert->conexao);
									}
									
									$autorizado = 'N';
									$historico .= " . Desautorizado por $usuario.";
								}
							}
						}
						if ($alterado) {
							if ($contAlt == 1) {
								$texto = "<html><body><meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>";
								$texto .= "<table border=1 cellspacing=1 cellpadding=2 align='center' bordercolor='#FFFFFF' bgcolor='#FFFFFF'>";
								$texto .= "<tr><td bgcolor='#99AACC' align='center' colspan='18'><font size='2' face='Arial'><b>Autorização de Contas a Pagar</b></font></td></tr>";
								$texto .= "<tr><td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'><b>Empresa</b></font></td>";
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>NUnico</font></td>";
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>NF</font></td>";
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>P</font></td>";
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>Natureza</font></td>";
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>Emissão</font></td>";
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>Prorrog</font></td>";
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>Solicit</font></td>";
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>Liberador</font></td>";					
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>Histórico</font></td>";					
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>Autorizado</font></td>";
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>Orig</font></td>";
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>Agend</font></td>";
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>Conf</font></td>";
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>Ent</font></td>";
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>Ctb</font></td>";
								$texto .= "<td bgcolor='#FAEFCC' align='center'><font size='2' face='Arial'>Valor</font></td></tr>";
							}
							
							if ($parceiroant2 <> $parceiro) {
								$parceiroant2 = $parceiro;
								if ($somaParc2 > 0) {
									$texto .=  "<tr><td bgcolor='#FFFFFF' align='center' colspan='18'><font size='2' face='Arial' color='#003366'><b>Total do Parceiro</b></font></td>";
									$texto .=  "<td bgcolor='#FFFFFF' align='right'><font size='2' face='Arial' color='#003366'><b>".number_format(str_replace(',','.',$somaParc2), 2, ',', '.')."</b></font></td></tr>";
									$somaParc2 = 0;
								}
								$texto .= "<tr><td bgcolor='#EEEEEE' colspan='19'><font size='2' face='Arial' color='#003366'><b>$parceiro&nbsp;-&nbsp;$nomeparc</b></font></td></tr>";
							}
							
							$texto .= "<tr><td bgcolor='#FFFFFF' align='center'><font size='2' face='Arial'>$codemp</font></td>";
							$texto .= "<td bgcolor='#FFFFFF' align='center'><font size='2' face='Arial'>$nufin</font></td>";
							$texto .= "<td bgcolor='#FFFFFF' align='center'><font size='2' face='Arial'>$numnota</font></td>";
							$texto .= "<td bgcolor='#FFFFFF' align='center'><font size='2' face='Arial'>$desdobramento</font></td>";
							$texto .= "<td bgcolor='#FFFFFF' align='center'><font size='2' face='Arial'>$codnat</font></td>";
							$texto .= "<td bgcolor='#FFFFFF' align='center'><font size='2' face='Arial'>$dtneg</font></td>";
							$texto .= "<td bgcolor='#FFFFFF' align='center'><font size='2' face='Arial'>$dtvenc</font></td>";
							$texto .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$nomesolicitante</font></td>";
							$texto .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$nomeusuautorizou</font></td>";
							$texto .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$historico</font></td>";
							$texto .= "<td bgcolor='#FFFFFF' align='center'><font size='2' face='Arial'>";
							if ($autorizado == 'S') $texto .= "Sim"; else $texto .= "Não";
							$texto .= "</font></td>";
							$texto .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$origem</font></td>";
							$texto .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_agendado,0,1) == 'S') $texto .= "Sim"; else $texto .= "Não";
							$texto .= "</font></td>";
							$texto .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_conferido,0,1) == 'S') $texto .= "Sim"; else $texto .= "Não";
							$texto .= "</font></td>";
							$texto .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_entcontab,0,1) == 'S') $texto .= "Sim"; else $texto .= "Não";
							$texto .= "</font></td>";
							$texto .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_conf_contab,0,1) == 'S') $texto .= "Sim"; else $texto .= "Não";
							$texto .= "</font></td>";
							$texto .= "<td bgcolor='#FFFFFF' align='right'><font size='2' face='Arial'>$valor</font></td></tr>";
							$somaParc2 = $somaParc2 + str_replace(',','.',$vetor[$cont]['valor']);
							$soma2 = $soma2 + str_replace(',','.',$vetor[$cont]['valor']);
						}
						
						// Adendado Financeiro 
						if ($btnagendfin && $campoagend[$nufin] == 'S' && $contarquivo > 0) {
							if ($grupo == 'Fin' || $grupo == 'GerFin' || $grupo == 'Dir') {
								if (substr($ad_agendado,0,1) <> 'S') {
									$contAltAge++;
									$alteradoAge = true;
									
									$sqlUpdate = "UPDATE TGFFIN SET AD_AGENDADO = 'S', AD_CODUSUFIN = '$codusuario'
												  WHERE NUFIN = $nufin AND RECDESP = -1 AND DHBAIXA IS NULL";
									if ($codemp > 500) {
										$bdsqlInsertEX->execute_query($sqlUpdate,$bdsqlInsertEX->conexao);
									} else {
										$bdsqlInsert->execute_query($sqlUpdate,$bdsqlInsert->conexao);
									}
									
									$ad_agendado = 'S';
									$historico .= " . Agendado por $usuario.";
								}
							}
						} else if ($btnagendfin) {
							if ($grupo == 'Fin' || $grupo == 'GerFin' || $grupo == 'Dir') {
								if (substr($ad_agendado,0,1) <> 'N') {
									$contAltAge++;
									$alteradoAge = true;
									
									$sqlUpdate = "UPDATE TGFFIN SET AD_AGENDADO = 'N'
												  WHERE NUFIN = $nufin AND RECDESP = -1 AND DHBAIXA IS NULL AND AD_AGENDADO = 'S'";
									if ($codemp > 500) {
										$bdsqlInsertEX->execute_query($sqlUpdate,$bdsqlInsertEX->conexao);
									} else {
										$bdsqlInsert->execute_query($sqlUpdate,$bdsqlInsert->conexao);
									}
									
									$ad_agendado = 'N';
									$historico .= " . Desagendado por $usuario.";
								}
							}
						}
						
						if ($alteradoAge && $contAltAge > 0) {
							$textoAge = "<html><body><meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>";
							$textoAge .= "<table border='1' cellspacing='1' cellpadding='2' align='center' bordercolor='#FFFFFF' bgcolor='#FFFFFF' width='400'>";
							$textoAge .= "<tr><td bgcolor='#99AACC' align='center' colspan='4'><font size='2' face='Arial'><b>Agendado pelo Financeiro</b></font></td></tr>";
							$textoAge .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Parceiro</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF' colspan='3'><font size='2' face='Arial'>$parceiro&nbsp;-&nbsp;$nomeparc</font></td></tr>";
							
							$textoAge .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Empresa</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$codemp</font></td>";
							$textoAge .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Origem</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if ($origem == 'E') $textoAge .= "Estoque"; else $textoAge .= "Financeiro";
							$textoAge .= "</font></td></tr>";
							
							$textoAge .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>NF</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$numnota</font></td>";
							$textoAge .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Parcela</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$desdobramento</font></td></tr>";
							$textoAge .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Emissão</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$dtneg</font></td>";
							$textoAge .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Prorrogação</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$dtvenc</font></td></tr>";
							$textoAge .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Solicit</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$nomesolicitante</font></td>";
							$textoAge .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Liberador</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$nomeusuautorizou</font></td></tr>";
							
							$textoAge .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Autorizado</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF' colspan='3'><font size='2' face='Arial'>";
							if ($autorizado == "S") $textoAge .= "Sim"; else $textoAge .= "Não";
							$textoAge .= "</font></td></tr>";
							
							$textoAge .= "<tr>";
							$textoAge .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Agend Fin</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_agendado,0,1) == "S") $textoAge .= "Sim"; else $textoAge .= "Não";
							$textoAge .= "</font></td>";
							$textoAge .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Conf Fin</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_conferido,0,1) == "S") $textoAge .= "Sim"; else $textoAge .= "Não";
							$textoAge .= "</font></td>";
							$textoAge .= "</tr>";
							
							$textoAge .= "<tr>";
							$textoAge .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Ent Ctb</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_entcontab,0,1) == "S") $textoAge .= "Sim"; else $textoAge .= "Não";
							$textoAge .= "</font></td>";
							$textoAge .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Conf Ctb</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_conf_contab,0,1) == "S") $textoAge .= "Sim"; else $textoAge .= "Não";
							$textoAge .= "</font></td>";
							$textoAge .= "</tr>";
							
							$textoAge .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Valor</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF' colspan='3'><font size='2' face='Arial'>$valor</font></td></tr>";
							
							$textoAge .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Histórico</b></font></td>";
							$textoAge .= "<td bgcolor='#FFFFFF' colspan='3'><font size='2' face='Arial'>$historico</font></td></tr>";
							
							$para = array();
							$textoAge .= "</table></body></html>";
							
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
							$mail->Subject = "Agendado pelo Financeiro"; // Define o Assunto
							$mail->Body = $textoAge; // Corpo da mensagem em formato HTML
							
							if ($emailsolicitante <> 'suporte@aguanativa.com.br') $mail->AddAddress("$emailsolicitante",""); // Email e Nome do destinatário		
							
							$para[0] = $ck_email;
							$para[1] = 'financeiro@aguanativa.com.br';
							//$para[2] = 'lauro@aguanativa.com.br';
							
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
						
						// Conferir Financeiro
						if ($btnconferirfin && $campofin[$nufin] == 'S' && $contarquivo > 0) {
							if ($grupo == 'Fin' || $grupo == 'GerFin') {
								if (substr($ad_conferido,0,1) <> 'S') {
									$contAltfin++;
									$alteradofin = true;
									
									// Conferindo o Financeiro
									$sqlUpdate = "UPDATE TGFFIN SET AD_CONFERIDO = 'S', AD_CODUSUFIN = '$codusuario'
												  WHERE NUFIN = $nufin AND RECDESP = -1 AND DHBAIXA IS NULL";
									if ($codemp > 500) {
										$bdsqlInsertEX->execute_query($sqlUpdate,$bdsqlInsertEX->conexao);
									} else {
										$bdsqlInsert->execute_query($sqlUpdate,$bdsqlInsert->conexao);
									}
									
									$ad_conferido = 'S';
									$historico .= " . Conferido o financeiro por $usuario.";
								}
							}
						}
						
						if ($alteradofin && $contAltfin > 0) {
							$textofin = "<html><body><meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>";
							$textofin .= "<table border='1' cellspacing='1' cellpadding='2' align='center' bordercolor='#FFFFFF' bgcolor='#FFFFFF' width='400'>";
							$textofin .= "<tr><td bgcolor='#99AACC' align='center' colspan='4'><font size='2' face='Arial'><b>Conferido pelo Financeiro</b></font></td></tr>";
							$textofin .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Parceiro</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF' colspan='3'><font size='2' face='Arial'>$parceiro&nbsp;-&nbsp;$nomeparc</font></td></tr>";
							
							$textofin .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Empresa</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$codemp</font></td>";
							$textofin .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Origem</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if ($origem == "E") $textofin .= "Estoque"; else $textofin .= "Financeiro";
							$textofin .= "</font></td></tr>";
							
							$textofin .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>NF</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$numnota</font></td>";
							$textofin .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Parcela</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$desdobramento</font></td></tr>";
							$textofin .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Emissão</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$dtneg</font></td>";
							$textofin .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Prorrogação</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$dtvenc</font></td></tr>";
							$textofin .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Solicit</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$nomesolicitante</font></td>";
							$textofin .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Liberador</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$nomeusuautorizou</font></td></tr>";
							
							$textofin .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Autorizado</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if ($autorizado == "S") $textofin .= "Sim"; else $textofin .= "Não";
							$textofin .= "</font></td></tr>";
							
							$textofin .= "<tr>";
							$textofin .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Agend Fin</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_agendado,0,1) == "S") $textofin .= "Sim"; else $textofin .= "Não";
							$textofin .= "</font></td>";
							$textofin .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Conf Fin</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_conferido,0,1) == "S") $textofin .= "Sim"; else $textofin .= "Não";
							$textofin .= "</font></td>";
							$textofin .= "</tr>";
							
							$textofin .= "<tr>";
							$textofin .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Ent Ctb</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_entcontab,0,1) == "S") $textofin .= "Sim"; else $textofin .= "Não";
							$textofin .= "</font></td>";
							$textofin .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Conf Ctb</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_conf_contab,0,1) == "S") $textofin .= "Sim"; else $textofin .= "Não";
							$textofin .= "</font></td>";
							$textofin .= "</tr>";
							
							$textofin .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Valor</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF' colspan='3'><font size='2' face='Arial'>$valor</font></td></tr>";
							
							$textofin .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Histórico</b></font></td>";
							$textofin .= "<td bgcolor='#FFFFFF' colspan='3'><font size='2' face='Arial'>$historico</font></td></tr>";
							
							$para = array();
							$textofin .= "</table></body></html>";
							
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
							$mail->Subject = "Conferido pelo Financeiro"; // Define o Assunto
							$mail->Body = $textofin; // Corpo da mensagem em formato HTML
							
							if ($emailsolicitante <> 'suporte@aguanativa.com.br') $mail->AddAddress("$emailsolicitante",""); // Email e Nome do destinatário		
							
							$para[0] = $ck_email;
							$para[1] = 'financeiro@aguanativa.com.br';
							//$para[2] = 'lauro@aguanativa.com.br';
							
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
						
						// Conferir Contabilidade
						if ($btnconferirctb && $campoctb[$nufin] == 'S' && $contarquivo > 0) {
							if ($grupo == 'Ctb' || $grupo == 'Sec' || $usuario == 'RAIANE' || $usuario == 'LETICIA') {
								if (substr($ad_conf_contab,0,1) <> 'S') {
									$contAltctb++;
									$alteradoctb = true;
									
									// Conferindo a contabilidade
									$sqlUpdate = "UPDATE TGFFIN SET AD_CONF_CONTAB = 'S', AD_CODUSUCTB = '$codusuario'
												  WHERE NUFIN = $nufin AND RECDESP = -1 AND DHBAIXA IS NULL";
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
							$textoctb .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Solicit</b></font></td>";
							$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$nomesolicitante</font></td>";
							$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Liberador</b></font></td>";
							$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$nomeusuautorizou</font></td></tr>";
							
							$textoctb .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Autorizado</b></font></td>";
							$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if ($autorizado == "S") $textoctb .= "Sim"; else $textoctb .= "Não";
							$textoctb .= "</font></td></tr>";
							
							$textoctb .= "<tr>";
							$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Agend Fin</b></font></td>";
							$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_agendado,0,1) == "S") $textoctb .= "Sim"; else $textoctb .= "Não";
							$textoctb .= "</font></td>";
							$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Conf Fin</b></font></td>";
							$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_conferido,0,1) == "S") $textoctb .= "Sim"; else $textoctb .= "Não";
							$textoctb .= "</font></td>";
							$textoctb .= "</tr>";
							
							$textoctb .= "<tr>";
							$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Ent Ctb</b></font></td>";
							$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_entcontab,0,1) == "S") $textoctb .= "Sim"; else $textoctb .= "Não";
							$textoctb .= "</font></td>";
							$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Conf Ctb</b></font></td>";
							$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
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
							//$para[1] = 'lauro@aguanativa.com.br';
							
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
						
						// Entregue Contabilidade
						if ($btnentreguectb && $campoent[$nufin] == 'S' && $contarquivo > 0) {
							if ($grupo == 'Ctb' || $grupo == 'Sec' || $usuario == 'RAIANE' || $usuario == 'LETICIA') {
								if (substr($ad_entcontab,0,1) <> 'S') {
									$contEntctb++;
									$alteradoentctb = true;
									
									// Entregando a contabilidade
									$sqlUpdate = "UPDATE TGFFIN SET AD_ENTCONTAB = 'S', AD_CODUSUCTB = '$codusuario'
												  WHERE NUFIN = $nufin AND RECDESP = -1 AND DHBAIXA IS NULL";
									if ($codemp > 500) {
										$bdsqlInsertEX->execute_query($sqlUpdate,$bdsqlInsertEX->conexao);
									} else {
										$bdsqlInsert->execute_query($sqlUpdate,$bdsqlInsert->conexao);
									}
									
									$ad_entcontab = 'S';
									$historico .= " . Entregue a contabilidade por $usuario.";
								}
							}
						}
						if ($alteradoentctb && $contEntctb > 0) {
							$textoctb = "<html><body><meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>";
							$textoctb .= "<table border='1' cellspacing='1' cellpadding='2' align='center' bordercolor='#FFFFFF' bgcolor='#FFFFFF' width='400'>";
							$textoctb .= "<tr><td bgcolor='#99AACC' align='center' colspan='4'><font size='2' face='Arial'><b>Entregue para Contabilidade</b></font></td></tr>";
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
							$textoctb .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Solicit</b></font></td>";
							$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$nomesolicitante</font></td>";
							$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Liberador</b></font></td>";
							$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>$nomeusuautorizou</font></td></tr>";
							
							$textoctb .= "<tr><td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Autorizado</b></font></td>";
							$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if ($autorizado == "S") $textoctb .= "Sim"; else $textoctb .= "Não";
							$textoctb .= "</font></td></tr>";

							$textoctb .= "<tr>";
							$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Agend Fin</b></font></td>";
							$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_agendado,0,1) == "S") $textoctb .= "Sim"; else $textoctb .= "Não";
							$textoctb .= "</font></td>";
							$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Conf Fin</b></font></td>";
							$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_conferido,0,1) == "S") $textoctb .= "Sim"; else $textoctb .= "Não";
							$textoctb .= "</font></td>";
							$textoctb .= "</tr>";
							
							$textoctb .= "<tr>";
							$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Ent Ctb</b></font></td>";
							$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
							if (substr($ad_entcontab,0,1) == "S") $textoctb .= "Sim"; else $textoctb .= "Não";
							$textoctb .= "</font></td>";
							$textoctb .= "<td bgcolor='#FAEFCC'><font size='2' face='Arial'><b>Conf Ctb</b></font></td>";
							$textoctb .= "<td bgcolor='#FFFFFF'><font size='2' face='Arial'>";
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
							$mail->Subject = "Entregue para Contabilidade"; // Define o Assunto
							$mail->Body = $textoctb; // Corpo da mensagem em formato HTML
							
							if ($emailsolicitante <> 'suporte@aguanativa.com.br') $mail->AddAddress("$emailsolicitante",""); // Email e Nome do destinatário		
							
							$para[0] = $ck_email;
							//$para[1] = 'lauro@aguanativa.com.br';
							
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
					}
					
					// COMMIT
					if ($bdsqlInsert->conexao <> '') $bdsqlInsert->commit($bdsqlInsert->conexao);
					if ($bdsqlInsertEX->conexao <> '') $bdsqlInsertEX->commit($bdsqlInsertEX->conexao);
				}
				
				if ($nomesolicitante == 'AGENDA') $nomesolicitante = 'Automático';
				if ($nomeusuautorizou == 'AGENDA') $nomeusuautorizou = 'Automático';
				
				if ($cb_anexo == 'SIM') {
					$visualizar = false;
					if ($contarquivo > 0 || $contarquivopgto > 0) $visualizar = true;
					else $somaParc = 0;
				} else if ($cb_anexo == 'NAO') {
					$visualizar = false;
					if ($contarquivo == 0 && $contarquivopgto == 0) $visualizar = true;
					else $somaParc = 0;
				}
				
				if ($visualizar) {
					$cornatureza = "#FFFFFF";
					
					if ($natvalidada == 'S') {
						$cornatureza = "#FFBBAA";
						$natureza .= " (VA)";
					} else if ($natconsumo == 'S') {
						$cornatureza = "#FFEEAA";
						$natureza .= " (CS)";
					} else if ($natunica == 'S') {
						$cornatureza = "#CCDDEE";
						$natureza .= " (UN)";
					} else if ($natfolha == 'S') {
						$cornatureza = "#DDEEAA";
						$natureza .= " (FO)";
					} else if ($natsemmeta == 'S') {
						$cornatureza = "#CCBBEE";
						$natureza .= " (SM)";
					}
?>
          <tr> 
            <td bgcolor="#FFFFFF" align="center" title="Autorização">
              <input type="checkbox" name="<?php echo 'campo['.$nufin.']';?>" value="S" <?php if (($ad_autoriza1 <> 'SIM' && !$podeautorizar) || $provisao == 'S') echo 'disabled';?> <?php if (($todos == 'S' && ($ad_autoriza1 == 'SIM' || $podeautorizar)) || $autorizado == 'S') echo 'checked'?>>
            </td>
            <td bgcolor="#FFFFFF" align="center">
			  <font size="2" face="Arial"><?php echo $codemp;?></font>
			</td>
            <td bgcolor="#FFFFFF" align="right">
<?php if ($origem == 'E') { ?> <a href="itens.php?PHPSESSID=<?php echo $PHPSESSID;?>&nunota=<?php echo $nunota;?>&numnota=<?php echo $numnota;?>&codemp=<?php echo $codemp;?>&parceiro=<?php echo $parceiro;?>"><font size="2" face="Arial"><?php echo $numnota;?></font></a>
<?php } else { ?> <font size="2" face="Arial"><?php echo $numnota;?></font> <?php } ?>
			</td>
		    <td bgcolor="#FFFFFF" align="center">
			  <font size="2" face="Arial"><a href="financeiro.php?PHPSESSID=<?php echo $PHPSESSID;?>&nufin=<?php echo $nufin;?>&codemp=<?php echo $codemp;?>"><?php echo $nufin;?></a></font>
		    </td>
            <td bgcolor="#FFFFFF" align="center">
			  <font size="2" face="Arial"><?php echo $desdobramento;?></font>
			</td>
			<td bgcolor="<?php echo $cornatureza;?>">
			  <font size="2" face="Arial"><?php echo $natureza;?></font>
			</td>
            <td bgcolor="#FFFFFF" align="center">
			  <font size="2" face="Arial"><?php echo $dtneg;?></font>
			</td>
            
<?php if ($podeprorrogartitulo && substr($ad_agendado,0,1) <> 'S') { ?>
			<td bgcolor="#FFFFFF" align="center">
			  <input type="text" name="<?php echo 'campodata['.$nufin.']';?>" size="5" maxlength="10" value="<?php echo $dtvenc;?>" onKeyUp="form2.btnprorrogar.disabled = false;" style="width: 55px; text-align: center; border: 0px; background-color: #EEEEEE; font-size: 13px">
            </td>
<?php } else { ?>
			<td bgcolor="#FFFFFF" align="center">
			  <font size="2" face="Arial"><?php echo $dtvenc;?></font>
			</td>
<?php } ?>
			<td bgcolor="#FFFFFF">
			  <font size="2" face="Arial"><?php echo $tipotitulo;?></font>
			</td>
            <td bgcolor="#FFFFFF">
			  <font size="2" face="Arial"><?php echo $nomesolicitante;?></font>
			</td>
            <td bgcolor="#FFFFFF">
			  <font size="2" face="Arial" color="<?php echo $corusuautorizou;?>" title="Vermelho = Falta Liberar; Laranja = Reprovado. Nº Pedido <?php echo $ad_numpedido;?>"><?php echo $nomeusuautorizou;?></font>
			</td>
            <td bgcolor="#FFFFFF">
			  <font size="2" face="Arial"><?php echo $historico;?></font>
			</td>
            <td bgcolor="#FFFFFF" align="center" >
			  <font size="2" face="Arial"><?php echo $provisao;?></font>
			</td>
            <td bgcolor="#FFFFFF" align="center">
			<?php
            	if ($origem == 'F') echo "<font size='3' face='Arial' color='#FF0000'><b>$origem</b></font>";
                else echo "<font size='2' face='Arial'>$origem</font>";
			?>
			</td>
			<td bgcolor="#FFFFFF" align="center">
			  <input type="checkbox" name="<?php echo 'campoagend['.$nufin.']';?>" value="S" <?php if ($grupo <> 'Fin' && $grupo <> 'GerFin' && $grupo <> 'Dir' && $usuario <> 'LAUROx') echo 'disabled';?> <?php if ($contarquivo == 0) { echo ' disabled '; echo " title='Para Agendar o lançamento é preciso anexar o arquivo.' "; } ?> <?php if ($exigirpedido) { echo ' disabled '; echo " title='Para Agendar o lançamento de origem financeira é preciso ter o número do pedido.' "; } ?> <?php if (($todosage == 'S' && ($grupo == 'Dir' || $grupo == 'Fin' || $grupo == 'GerFin')) || substr($ad_agendado,0,1) == 'S') echo 'checked'?>>
			</td>
            <td bgcolor="#FFFFFF" align="center">
			  <input type="checkbox" name="<?php echo 'campofin['.$nufin.']';?>" value="S" <?php if ($grupo <> 'Fin' && $grupo <> 'GerFin') echo 'disabled';?> <?php if ($contarquivo == 0) { echo ' disabled '; echo " title='Para Conferir o lançamento é preciso anexar o arquivo.' "; } ?> <?php if (($todosfin == 'S' && ($grupo == 'Dir' || $grupo == 'Fin' || $grupo == 'GerFin')) || substr($ad_conferido,0,1) == 'S') echo 'checked'?>>
			</td>
			<td bgcolor="#FFFFFF" align="center">
			  <input type="checkbox" name="<?php echo 'campoent['.$nufin.']';?>" value="S" <?php if ($grupo <> 'Ctb' && $grupo <> 'Sec' && $usuario <> 'RAIANE' && $usuario <> 'LETICIA') echo 'disabled';?> <?php if ($contarquivo == 0) { echo ' disabled '; echo " title='Para Conferir a entrega é preciso anexar o arquivo.' "; } ?> <?php if (($todosent == 'S' && ($grupo == 'Dir' || $grupo == 'Ctb' || $grupo == 'Sec' || $usuario == 'RAIANE' || $usuario == 'LETICIA')) || substr($ad_entcontab,0,1) == 'S') echo 'checked'?>>
			</td>
            <td bgcolor="#FFFFFF" align="center">
			  <input type="checkbox" name="<?php echo 'campoctb['.$nufin.']';?>" value="S" <?php if ($grupo <> 'Ctb' && $grupo <> 'Sec' && $usuario <> 'RAIANE' && $usuario <> 'LETICIA') echo 'disabled';?> <?php if ($contarquivo == 0) { echo ' disabled '; echo " title='Para Conferir o lançamento é preciso anexar o arquivo.' "; } ?> <?php if (($todosctb == 'S' && ($grupo == 'Dir' || $grupo == 'Ctb' || $grupo == 'Sec' || $usuario == 'RAIANE' || $usuario == 'LETICIA')) || substr($ad_conf_contab,0,1) == 'S') echo 'checked'?>>
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
		// Vetor Arquivo
		if ($contarquivopgto > 0) {
			for ($a=1; $a<=sizeof($v_arquivopgto); $a++) {
            	$arquivo4 = $v_arquivopgto[$a]['arquivo'];
				$arquivo5 = substr($arquivo4,9,60);
?>
				<a href="<?php echo $arquivo4;?>" target="_blank" title="<?php echo $arquivo5;?>"><img src="img/anexo4.jpg" border="0" width="17"></a>
<?php 
			}
		} else echo '&nbsp;';
?>
			</td>
			
            <td bgcolor="#FFFFFF" align="right">
			  <font size="2" face="Arial"><?php echo $valor;?></font>
			</td>
          </tr>
<?php
				}
			}
		}
	}
	if ($somaParc > 0) {
		$texto .=  "<tr><td bgcolor='#FFFFFF' align='center' colspan='16'><font size='2' face='Arial' color='#003366'><b>Total&nbsp;do&nbsp;Parceiro</b></font></td>";
		$texto .=  "<td bgcolor='#FFFFFF' align='right'><font size='2' face='Arial' color='#003366'><b>".number_format(str_replace(',','.',$somaParc2), 2, ',', '.')."</b></font></td></tr>";
	}
	
	$texto .=  "<tr><td bgcolor='#FAEFCC' align='center' colspan='16'><font size='2' face='Arial'><b>Total Geral</b></font></td>";
	$texto .=  "<td bgcolor='#FAEFCC' align='right'><font size='2' face='Arial'><b>".number_format(str_replace(',','.',$soma2), 2, ',', '.')."</b></font></td></tr>";
	
	// COMMIT
	if ($bdsqlInsert->conexao <> '') $bdsqlInsert->commit($bdsqlInsert->conexao);
	if ($bdsqlInsertEX->conexao <> '') $bdsqlInsertEX->commit($bdsqlInsertEX->conexao);
	
	if ($contAlt > 0) {
		$texto .= "</table></body></html>";
		
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
        $mail->Subject = "Autorizacao de Contas a Pagar"; // Define o Assunto
        $mail->Body = $texto; // Corpo da mensagem em formato HTML
		
		$mail->AddAddress('financeiro@aguanativa.com.br',""); // Email e Nome do destinatário		
		
		$para[0] = $ck_email;
		$para[1] = 'ronaldo@aguanativa.com.br';
		//$para[2] = 'lauro@aguanativa.com.br';
		
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
	
	echo "<tr><td bgcolor='#FFFFFF' align='center' colspan='20'><font size='2' face='Arial' color='#003366'><b>Total do Parceiro</b></font></td>";
	echo "<td bgcolor='#FFFFFF' align='right'><font size='2' face='Arial' color='#003366'><b>".number_format(str_replace(',','.',$somaParc), 2, ',', '.')."</b></font></td></tr>";
	
	$soma = number_format(str_replace(',','.',$soma), 2, ',', '.');
	

?>
          <tr bgcolor="#FAEFCC" bordercolor="#FAEFCC"> 
            <td colspan="20"> 
              <div align="center"><b><font face="Arial" size="2">Total</font></b></div>
            </td>
            <td align="right"><b><font face="Arial" size="2"> 
              <?php echo $soma;?>
              </font></b>
			</td>
          </tr>
        </table>
		
<?php if ($podeautorizar && $cb_provisao <> 'S') { ?>
      <br>
      <div align="center">
        <input type="submit" name="btnautorizar" value="Autorizar">
		&nbsp;&nbsp;&nbsp;
		<input type="submit" name="btndesautorizar" value="Desautorizar">
      </div>
<?php } ?>

<?php if ($grupo == 'Fin' || $grupo == 'GerFin' || $grupo == 'Dir' || $usuario == 'LAUROx') { ?>
      <br>
      <div align="center">
        <input type="submit" name="btnagendfin" value="Agendar Financeiro">
      </div>
<?php } ?>

<?php if ($grupo == 'Fin' || $grupo == 'GerFin') { ?>
      <br>
      <div align="center">
        <input type="submit" name="btnconferirfin" value="Conferir Financeiro">
      </div>
<?php } ?>

<?php if ($grupo == 'Ctb' || $grupo == 'Sec' || $usuario == 'RAIANE' || $usuario == 'LETICIA') { ?>
      <br>
      <div align="center">
        <input type="submit" name="btnentreguectb" value="Entregue Contabilidade">
      </div>
<?php } ?>

<?php if ($grupo == 'Ctb' || $grupo == 'Sec' || $usuario == 'RAIANE' || $usuario == 'LETICIA') { ?>
      <br>
      <div align="center">
        <input type="submit" name="btnconferirctb" value="Conferir Contabilidade">
      </div>
<?php } ?>

<?php if ($podeprorrogar) { ?>
	  <br>
      <div align="center" title="Altere a data primeiro">
        <input type="submit" name="btnprorrogar" value="Prorrogar" disabled>
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
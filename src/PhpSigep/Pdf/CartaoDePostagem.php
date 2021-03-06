<?php
namespace PhpSigep\Pdf;

use PhpSigep\Model\ServicoDePostagem;
use PhpSigep\Pdf\Chancela\Pac;
use PhpSigep\Pdf\Chancela\Sedex;

/**
 * @author: Stavarengo
 */
class CartaoDePostagem
{

	/**
	 * @var \PhpSigep\Pdf\ImprovedFPDF
	 */
	public $pdf;
	/**
	 * @var \PhpSigep\Model\PreListaDePostagem
	 */
	private $plp;
	/**
	 * @var int
	 */
	private $idPlpCorreios;
	/**
	 * @var string
	 */
	private $logoFile;

	/**
	 * @param \PhpSigep\Model\PreListaDePostagem $plp
	 * @param int $idPlpCorreios
	 * @param string $logoFile
	 */
	public function __construct($plp, $idPlpCorreios, $logoFile)
	{
		$this->plp           = $plp;
		$this->idPlpCorreios = $idPlpCorreios;
		$this->logoFile = $logoFile;

		$this->init();
	}

	public function render()
	{
		$un               = 72 / 25.4;
		$wFourAreas       = $this->pdf->w / 2;
		$hFourAreas       = ($this->pdf->h - ($un * 15)) / 2; //-Menos 1.5CM porque algumas impressoras não conseguem imprimir nos ultimos 1cm da página 
		$tMarginFourAreas = $un * 5;
		$rMarginFourAreas = $un * 5;
		$bMarginFourAreas = $un * 5;
		$lMarginFourAreas = $un * 5;
		$wInnerFourAreas  = $wFourAreas - $lMarginFourAreas - $rMarginFourAreas;
		$hInnerFourAreas  = $hFourAreas - $tMarginFourAreas - $bMarginFourAreas;

		$margins = array(
			array(
				'l' => $lMarginFourAreas,
				'r' => $wFourAreas - $rMarginFourAreas,
				't' => $tMarginFourAreas,
				'b' => $hFourAreas - $bMarginFourAreas
			),
			array(
				'l' => $wFourAreas + $lMarginFourAreas,
				'r' => $wFourAreas * 2 - $rMarginFourAreas,
				't' => $tMarginFourAreas,
				'b' => $hFourAreas - $bMarginFourAreas,
			),
			array(
				'l' => $lMarginFourAreas,
				'r' => $wFourAreas - $rMarginFourAreas,
				't' => $hFourAreas + $tMarginFourAreas,
				'b' => $hFourAreas * 2 - $bMarginFourAreas,
			),
			array(
				'l' => $wFourAreas + $lMarginFourAreas,
				'r' => $wFourAreas * 2 - $rMarginFourAreas,
				't' => $hFourAreas + $tMarginFourAreas,
				'b' => $hFourAreas * 2 - $bMarginFourAreas,
			),
		);

		$objetosPostais = $this->plp->getEncomendas();
		$total          = count($objetosPostais);
		while (count($objetosPostais)) {
			$this->pdf->AddPage();
			$crossMargin = 50;
			$this->pdf->SetDrawColor(200, 200, 200);
			for ($lineY = $crossMargin; $lineY < $this->pdf->h - $crossMargin; $lineY += 10) {
				$this->pdf->Line($wFourAreas, $lineY, $wFourAreas, $lineY + 3);
			}
			for ($lineX = $crossMargin; $lineX < $this->pdf->w - $crossMargin; $lineX += 10) {
				$this->pdf->Line($lineX, $hFourAreas, $lineX + 3, $hFourAreas);
			}

			$this->pdf->SetDrawColor(0, 0, 0);
			for ($area = 0; $area < 4; $area++) {
				if (!count($objetosPostais)) break;
				/** @var $objetoPostal \PhpSigep\Model\ObjetoPostal */
				$objetoPostal = array_shift($objetosPostais);

				$lPosFourAreas = $margins[$area]['l'];
				$rPosFourAreas = $margins[$area]['r'];
				$tPosFourAreas = $margins[$area]['t'];
				$bPosFourAreas = $margins[$area]['b'];

				$this->pdf->Line($lPosFourAreas, $tPosFourAreas, $lPosFourAreas, $bPosFourAreas);
				$this->pdf->Line($lPosFourAreas, $tPosFourAreas, $rPosFourAreas, $tPosFourAreas);
				$this->pdf->Line($rPosFourAreas, $tPosFourAreas, $rPosFourAreas, $bPosFourAreas);
				$this->pdf->Line($lPosFourAreas, $bPosFourAreas, $rPosFourAreas, $bPosFourAreas);

				// Logo
				$this->pdf->SetXY($lPosFourAreas, $tPosFourAreas);
				$this->setFillColor(222, 222, 222);
				$headerColWidth = $wInnerFourAreas / 3;
				$headerHeigth   = 106;
//				$this->t($headerColWidth, 'Logo', 0, 'C', $headerHeigth);
				$this->pdf->Image($this->logoFile);
				$this->pdf->Line($headerColWidth + $lPosFourAreas, $tPosFourAreas, $headerColWidth + $lPosFourAreas, $tPosFourAreas + $headerHeigth);
				$this->pdf->Line($lPosFourAreas, $tPosFourAreas + $headerHeigth, $lPosFourAreas + $headerColWidth, $tPosFourAreas + $headerHeigth);

				// Título da etiqueta
				$lPosHeaderCol2 = $headerColWidth + $lPosFourAreas;
				$this->pdf->SetXY($lPosHeaderCol2, $tPosFourAreas);
				$this->setFillColor(200, 200, 200);
				$this->pdf->SetFontSize(15);
				$this->pdf->SetFont('', 'B');
				$this->t($headerColWidth * 2, 'Cartão de Postagem', 2, 'C');

				// Número da plp
				$this->setFillColor(150, 200, 200);
				$this->pdf->SetFont('', '');
				$this->pdf->SetFontSize(10);
				$this->t($headerColWidth * 2, $this->idPlpCorreios, 0, 'C');

				// Chancela
				$this->setFillColor(150, 150, 200);
				$wChancela    = 101.5;
				$hChancela    = 72.5;
				$lPosChancela = $rPosFourAreas - $wChancela;
				$bPosHeader   = $tPosFourAreas + $headerHeigth;
				$tPosChancela = $bPosHeader - $hChancela;
				$this->pdf->SetXY($lPosChancela, $tPosChancela);
				//$this->t($wChancela, 'Chancela', 0, 'C', $hChancela);
				$servicoDePostagem = $objetoPostal->getServicoDePostagem();
				$nomeRemetente     = $this->plp->getRemetente()->getNome();
				$accessData        = $this->plp->getAccessData();
				if ($servicoDePostagem->is(ServicoDePostagem::SERVICE_SEDEX_40436)
					|| $servicoDePostagem->is(ServicoDePostagem::SERVICE_SEDEX_40096)
					|| $servicoDePostagem->is(ServicoDePostagem::SERVICE_SEDEX_40444)
					|| $servicoDePostagem->is(ServicoDePostagem::SERVICE_SEDEX_AGRUPADO)
				) {
					$chancela = new Sedex($lPosChancela, $tPosChancela, $nomeRemetente, Sedex::SERVICE_SEDEX, $accessData);
				} else if ($servicoDePostagem->is(ServicoDePostagem::SERVICE_SEDEX_10_PACOTE)
					|| $servicoDePostagem->is(ServicoDePostagem::SERVICE_SEDEX_10_ENVELOPE)
				) {
					$chancela = new Sedex($lPosChancela, $tPosChancela, $nomeRemetente, Sedex::SERVICE_SEDEX_10, $accessData);
				} else if ($servicoDePostagem->is(ServicoDePostagem::SERVICE_E_SEDEX)) {
					$chancela = new Sedex($lPosChancela, $tPosChancela, $nomeRemetente, Sedex::SERVICE_E_SEDEX, $accessData);
				} else if ($servicoDePostagem->is(ServicoDePostagem::SERVICE_SEDEX_HOJE)) {
					$chancela = new Sedex($lPosChancela, $tPosChancela, $nomeRemetente, Sedex::SERVICE_SEDEX_HOJE, $accessData);
				} else if ($servicoDePostagem->is(ServicoDePostagem::SERVICE_PAC)) {
					$chancela = new Pac($lPosChancela, $tPosChancela, $nomeRemetente, $accessData);
				} else if ($servicoDePostagem->is(ServicoDePostagem::SERVICE_CARTA)
					|| $servicoDePostagem->is(ServicoDePostagem::SERVICE_CARTA_REGISTRADA)
				) {
//					$chancela = new Sedex($lPosChancela, $tPosChancela, $nomeRemetente, Sedex::SERVICE_SEDEX_HOJE, $accessData);
				}

				if ($chancela) {
					$chancela->draw($this->pdf);
				}

				// Peso
				$this->setFillColor(100, 150, 200);
				$this->pdf->SetFontSize(9);
				$lineHeigth = $this->pdf->getLineHeigth(100 / $this->pdf->k);
				$this->pdf->SetXY($lPosHeaderCol2, $bPosHeader - $lineHeigth * 2);
				$this->t($lPosChancela - $lPosHeaderCol2, 'Peso: ' . ((float)$objetoPostal->getPeso()) . 'g', 2, 'C', $lineHeigth);

				// Volume
				$this->setFillColor(100, 150, 200);
//				$this->t($lPosChancela - $lPosHeaderCol2, 'Volume: ' . ($total - count($objetosPostais)) . '/' . $total, 0, 'C', $lineHeigth);
				$this->t($lPosChancela - $lPosHeaderCol2, 'Volume: 1/1', 0, 'C', $lineHeigth);

				// Número da etiqueta
				$this->setFillColor(100, 100, 200);
				$this->pdf->SetXY($lPosFourAreas, $bPosHeader + 5);
				$this->pdf->SetFontSize(10);
				$this->pdf->SetFont('', 'B');
				$etiquetaComDv = $objetoPostal->getEtiqueta()->getEtiquetaComDv();
				$this->t($wInnerFourAreas, $etiquetaComDv, 1, 'C');

				// Código de barras da etiqueta
				$this->setFillColor(0, 0, 0);
				$hEtiquetaBarCode    = 40;
				$tPosEtiquetaBarCode = $this->pdf->GetY();
				$wEtiquetaBarCode    = $un * 65.44;
				$code128             = new \PhpSigep\Pdf\Script\BarCode128();
				$code128->draw($this->pdf, $lPosFourAreas + $wInnerFourAreas / 2 - $wEtiquetaBarCode / 2, $tPosEtiquetaBarCode, $etiquetaComDv, $wEtiquetaBarCode, $hEtiquetaBarCode);

				// Destinatário
				$wAddressLeftCol  = $wInnerFourAreas / 4 * 2.2;
				$wAddressRightCol = $wInnerFourAreas - $wAddressLeftCol;
				$lAddressRigthCol = $wAddressLeftCol + $lPosFourAreas;

				$tPosAfterBarCode = $tPosEtiquetaBarCode + $hEtiquetaBarCode + 5;
				$t                = $this->writeDestinatario($lPosFourAreas, $tPosAfterBarCode, $wAddressLeftCol, $objetoPostal);

				$t += $this->pdf->getLineHeigth() / 2;
				$this->writeRemetente($lPosFourAreas, $t, $wAddressLeftCol, $this->plp->getRemetente());

				$destino     = $objetoPostal->getDestino();
				$hCepBarCode = 0;
				if ($destino instanceof \PhpSigep\Model\DestinoNacional) {
					// Número do CEP
					$cep = $destino->getCep();
					$cep = preg_replace('/[^\d]/', '', $cep);
					$this->setFillColor(215, 115, 15);
					$this->pdf->SetXY($lAddressRigthCol, $tPosAfterBarCode + 15);
					$this->t($wAddressRightCol, $cep, 2, 'C');
					$tPosCepBarCode = $this->pdf->GetY();

					// Etiqueta do CEP
					$hCepBarCode = 25;
					$wCepBarCode = $un * 38.44;
					$this->setFillColor(0, 0, 0);
					$code128->draw($this->pdf, $lAddressRigthCol + $wAddressRightCol / 2 - $wCepBarCode / 2, $tPosCepBarCode, $cep, $wCepBarCode, $hCepBarCode);
				}
			}
		}

		$this->pdf->Output();
	}

	private function _($str)
	{
		if (extension_loaded('iconv')) {
			return iconv('UTF-8', 'ISO-8859-1', $str);
		} else {
			return utf8_decode($str);
		}
	}

	private function init()
	{
		$this->pdf = new \PhpSigep\Pdf\ImprovedFPDF('P', 'pt');
		$this->pdf->SetFont('Arial', '', 10);
	}

	/**
	 * @param $l
	 * @param $tPosEtiquetaBarCode
	 * @param $hEtiquetaBarCode
	 * @param $w
	 * @param $lineHeigth
	 * @param $objetoPostal
	 */
	private function writeDestinatario($l, $t, $w, $objetoPostal)
	{
		$titulo           = 'Destinatário';
		$nomeDestinatario = $objetoPostal->getDestinatario()->getNome();
		$logradouro       = $objetoPostal->getDestinatario()->getLogradouro();
		$numero           = $objetoPostal->getDestinatario()->getNumero();
		$complemento      = $objetoPostal->getDestinatario()->getComplemento();
		$bairro           = '';
		$cidade           = '';
		$uf               = '';
		$destino          = $objetoPostal->getDestino();
		if ($destino instanceof \PhpSigep\Model\DestinoNacional) {
			$bairro = $destino->getBairro();
			$cidade = $destino->getCidade();
			$uf     = $destino->getUf();
		}

		return $this->writeEndereco($t, $l, $w, $titulo, $nomeDestinatario, $logradouro, $numero, $complemento, $bairro, $cidade, $uf);
	}

	private function writeRemetente($l, $t, $w, \PhpSigep\Model\Remetente $remetente)
	{
		$titulo           = 'Remetente';
		$nomeDestinatario = $remetente->getNome();
		$logradouro       = $remetente->getLogradouro();
		$numero           = $remetente->getNumero();
		$complemento      = $remetente->getComplemento();
		$bairro           = $remetente->getBairro();
		$cidade           = $remetente->getCidade();
		$uf               = $remetente->getUf();

		return $this->writeEndereco($t, $l, $w, $titulo, $nomeDestinatario, $logradouro, $numero, $complemento, $bairro, $cidade, $uf);
	}

	/**
	 * @param $t
	 * @param $l
	 * @param $w
	 * @param $titulo
	 * @param $nomeDestinatario
	 * @param $logradouro
	 * @param $numero1
	 * @param $complemento
	 * @param $bairro
	 * @param $cidade
	 * @param $uf
	 *
	 * @internal param $lineHeigth
	 * @internal param $objetoPostal
	 */
	private function writeEndereco($t, $l, $w, $titulo, $nomeDestinatario, $logradouro, $numero1, $complemento, $bairro,
		$cidade, $uf
	) {
		// Titulo do bloco: destinatario ou remetente
		$this->pdf->SetFont('', 'B');
		$this->setFillColor(60, 60, 60);
		$this->pdf->SetFontSize(9);
		$this->pdf->SetXY($l, $t);
		$this->t($w, $titulo, 2, '');

		$addressPadding = 5;
		$w              = $w - $addressPadding;
		$l              = $l + $addressPadding;

		// Nome da pessoa
		$this->pdf->SetFont('', '');
		$this->setFillColor(190, 190, 190);
		$this->pdf->SetX($l);
		$this->multiLines($w, $nomeDestinatario, 'L');

		//Primeria parte do endereco
		$address1 = $logradouro;
		$numero   = $numero1;
		if (!$numero || strtolower($numero) == 'sn') {
			$address1 .= ', s/ nº';
		} else {
			$address1 .= ', #' . $numero;
		}
		if ($complemento) {
			$address1 .= ' - ' . $complemento;
		}
		$this->setFillColor(100, 190, 190);
		$this->pdf->SetX($l);
		$this->multiLines($w, $address1, 'L');

		//Segunda parte do endereco
		$this->pdf->SetX($l);
		$this->setFillColor(100, 130, 190);
		$this->multiLines($w, 'Bairro: ' . $bairro, 'L');
		$this->setFillColor(100, 30, 210);
		$this->pdf->SetX($l);
		$this->multiLines($w, $cidade . ' - ' . $uf, 'L');

		return $this->pdf->GetY();
	}

	private function setFillColor($r, $g, $b)
	{
		$this->pdf->SetFillColor($r, $g, $b);
	}

	private function t($w, $txt, $ln, $align, $h = null, $multiLines = false, $utf8 = true)
	{
		if ($utf8) {
			$txt = $this->_($txt);
		}
//		$border = 1;
//		$fill   = true;
		$border = 0;
		$fill   = false;

		if ($h === null) {
			$h = $this->pdf->getLineHeigth();
		}

		if ($multiLines) {
			$this->pdf->MultiCell($w, $h, $txt, $border, $align, $fill);
		} else {
			$this->pdf->Cell($w, $h, $txt, $border, $ln, $align, $fill);
		}
	}

	private function multiLines($w, $txt, $align, $h = null, $utf8 = true)
	{
		$this->t($w, $txt, null, $align, $h, true, $utf8);
	}
}
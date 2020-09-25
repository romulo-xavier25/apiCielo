<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cielo\API30\Merchant;
use Cielo\API30\Ecommerce\Environment;
use Cielo\API30\Ecommerce\Sale;
use Cielo\API30\Ecommerce\CieloEcommerce;
use Cielo\API30\Ecommerce\Payment;
use Cielo\API30\Ecommerce\CreditCard;
use Cielo\API30\Ecommerce\Request\CieloRequestException;

class CieloController extends Controller
{

	$private $environment;
	$private $merchant;
	$private $cielo;
	$private $sale;
	$private $payment;

	public function __construct(Request $request)
	{
		/* 
		Usar quando projeto estiver em produção
		$this->environment = Environment::production();
		*/
		
		// Quando projeto estiver em produção, remover linha abaixo
		$this->environment = Environment::sandbox();
		$this->merchant = new Merchant(config('cielo.MechantId'), config('cielo.MechantKEY'));
		$this->cielo = new CieloEcommerce($this->merchant, $this->environment);
		$this->sale = new Sale('123');
	}


    public function peyer(Request $request)
    {

		// Crie uma instância de Customer informando o nome do cliente
		$this->sale->customer($request->holder);

		// Crie uma instância de Payment informando o valor do pagamento
		$this->paymentInit($request->price);

		// Crie uma instância de Credit Card utilizando os dados de teste
		// esses dados estão disponíveis no manual de integração
		$this->cardData($request->price, $request->cvv, $request->data, $request->numberCard, $request->holder);

		// Crie o pagamento na Cielo
		try {
		    // Configure o SDK com seu merchant e o ambiente apropriado para criar a venda
		    $this->createSale();

		    // Com o ID do pagamento, podemos fazer sua captura, se ela não tiver sido capturada ainda
		    $this->captureSale($request->price);

		    // E também podemos fazer seu cancelamento, se for o caso
		    $this->cancelSale($request->price);
		} catch (CieloRequestException $e) {
		    // Em caso de erros de integração, podemos tratar o erro aqui.
		    // os códigos de erro estão todos disponíveis no manual de integração.
		    $error = $e->getCieloError();
		}

    }

    private function createSale()
    {
    	return ($this->cielo)->createSale($this->sale);
    }

    private function captureSale($price)
    {
    	return ($this->cielo)->captureSale($this->paymentId(), $price, 0);
    }

    private function cancelSale($price)
    {
    	return ($this->cielo)->cancelSale($this->paymentId(), $price);
    }

    private function paymentInit($price)
    {
    	return $this->sale->payment($price);
    }

    private function paymentId()
    {
    	return $this->createSale()->getPayment()->getPaymentId();
    }

    private function cardData($price, $cvv, $data, $numberCard, $holder)
    {
    	$this->paymentInit($price)->setType($this->payment)
		        ->creditCard($cvv, CreditCard::VISA)
		        ->setExpirationDate($data)
		        ->setCardNumber($numberCard)
		        ->setHolder($holder);
    }

}

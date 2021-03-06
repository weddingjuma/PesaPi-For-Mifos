<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Core extends CI_Controller {


    function __construct(){
        parent::__construct();
        $this->load->model('LocalDBHandler');
    }

    public function receivePaymentViaMpesa(){

        load_this_controller('MpesaClientHandler');

        if(ENVIRONMENT == "production"){
            $data =  $this->MpesaClientHandler_->getPesaPiPostedData();
        }else{
            $data['receipt']= "test_receipt";
            $data['type'] = 6;
            $data['time'] = time();
            $data['phoneNumber'] = '0707070707';
            $data['name'] = 'testAccount';
            $data['account'] = '';
            $data['amount'] = 100; //Convert Amount From cents To Shillings
            $data['postBalance'] = 5000; //Convert Balance From Cents To shillings
            $data['transactionCost'] = '0';
            $data['secret'] = 'FWv{VvB7#dSsJ(\S5_f)3C3S';
        }

        if($this->MpesaClientHandler_->dataHasErrors($data)) {
            print_r($this->errorMessage);
            die();
        }

        $client = $this->MpesaClientHandler_->findClientByPhoneNumber($data['phoneNumber']);
        $data['clientID'] = $client->entityId;

        $clientHasAnActiveLoan = $this->MpesaClientHandler_->clientHasActiveLoanAccount($data);

        if($clientHasAnActiveLoan){
            $outPut = $this->MpesaClientHandler_->makeRepaymentToALoanAccount($data);
        }else{
            $outPut = $this->MpesaClientHandler_->makeDepositToClientSavingsAccount($data);
        }

        //Confirm That amount has been posted successfully
        if(array_key_exists('resourceId',$outPut['result'])){

            $this->LocalDBHandler->recordTransactionThatHaveBeenPostedToMifosDatabase($outPut['data']);
            $data['messageBody'] = "Confirmed.\r\n Amount of Ksh " . $data['amount'] . " has been credited to your Jambo Account.";
            $this->MpesaClientHandler_->sendMessageToClient($data);
        }

    }

    public function index(){

        $data['mpesaTransactions'] = $this->LocalDBHandler->getMpesaTransactions();
        $this->load->view('MpesaTransactions', $data);
    }



}

<?php
define('PAY_CHANNELS_PAY_REQUEST', 'pay_channels_pay_request');

require_once 'includes/wp-pay-channels-create-transactions-table.php';

class PayChannelPayForm{
    /**
     * @var string
     */
    private $action;
    /**
     * @var string
     */
    private $method;
    /**
     * @var array
     */
    private $body;

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param array $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }
}

class PayChannelTransactionsRepository{

    static function createWithSetId($transaction){
        #TODO : insert and set id transaction
        return $transaction;
    }

    static function findById($transactionId){
        #TODO : find by transaction id
        return null;
    }

    static function update($transaction){
        #TODO : update transaction
        return $transaction;
    }
}

class PayChannelTransactionFactory{

    static function makeWithArray($request){
        $transaction = new PayChannelTransaction('');

        $transaction->setAmount($request['amount']);
        $transaction->setInvoiceCode($request['invoice_code']);
        $transaction->setChannelName($request['channel']);
        $transaction->setOwnerCallbackUrl($request['callback_url']);

        return $transaction;
    }
}

class PayChannelTransaction
{
    const STATUS_CREATE = 'create';
    const STATUS_PENDING = 'pending';
    const STATUS_PAYING = 'paying';
    const STATUS_PAID = 'paid';
    const STATUS_REFUNDS = 'refunds';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_CANCELED = 'canceled';
    const STATUS_SUCCESS = 'success';
	/**
	 * @var string
	 */
	private $invoiceCode;
	/**
	 * @var string
	 */
	private $channelName;
	/**
	 * @var intiger
	 */
	private $amount;
	/**
	 * @var string
	 */
	private $callbackUrl;
    /**
     * @var string
     */
    private $ownerCallbackUrl;

	/**
	 * @var array
	 */
	private $data;

	function __construct($callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * @return string
     */
    public function getInvoiceCode()
    {
        return $this->invoiceCode;
    }

    /**
     * @param string $invoiceCode
     */
    public function setInvoiceCode($invoiceCode)
    {
        $this->invoiceCode = $invoiceCode;
    }

    /**
     * @return string
     */
    public function getChannelName()
    {
        return $this->channelName;
    }

    /**
     * @param string $channelName
     */
    public function setChannelName($channelName)
    {
        $this->channelName = $channelName;
    }


    /**
     * @return intiger
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param intiger $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    /**
     * @return string
     */
    public function getOwnerCallbackUrl()
    {
        return $this->ownerCallbackUrl;
    }

    /**
     * @param string $ownerCallbackUrl
     */
    public function setOwnerCallbackUrl($ownerCallbackUrl)
    {
        $this->ownerCallbackUrl = $ownerCallbackUrl;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

}

function wp_pay_channels_channel_selection( $atts ){
    $channels = [
        [
            'name' => 'credit',
            'label' => 'اعتباری',
            'image' => 'http://s.ir/mellat.jpg'
        ]
    ];

    $channels = apply_filters('wp_pay_channels_getways', []);
    ?>
    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
        <?php foreach ($channels as $channel) {?>
            <input type="radio" name="channel" value="<?php echo $channel['name']?>"> <?php echo $channel['label']?><br>
        <?php }?>
        <input type="hidden" name="action" value="<?php echo PAY_CHANNELS_PAY_REQUEST; ?>">
        <input type="hidden" name="amount" value="<?php echo $atts['amount']?>">
        <button type="submit">Pay</button>
    </form>
    <?php
}

function pay_channels_pay_request() {
	#TODO: Request Validation

    $transaction = PayChannelTransactionFactory::makeWithArray($_POST);

    $transaction = PayChannelTransactionsRepository::createWithSetId($transaction);

    $transaction = apply_filters('${channel}_pay_channel_transaction', $transaction);
    $transaction = PayChannelTransactionsRepository::update($transaction);

    $payForm = new PayChannelPayForm();
    $payForm = apply_filters('${channel}_pay_channel_pay_form', $payForm, $transaction);
    #TODO: Submit payForm
    // Handle request then generate response using echo or leaving PHP and using HTML
}

function pay_channels_pay_answer() {
    #TODO: Request Validation
    $transactionId = 11;

    $transaction = PayChannelTransactionsRepository::findById($transactionId);
    if(!$transaction){
        #TODO pay answer request not acceptable handle, transaction not found
    }
    $transaction->setStatus('');

    $transaction = apply_filters('${channel}_pay_channel_pay_answer', $transaction);
    $transaction = PayChannelTransactionsRepository::update($transaction);

    switch ($transaction->getStatus()){
        case PayChannelTransaction::STATUS_PAID:
            $transaction = apply_filters($transaction->getOwner() . '_pay_channel_paid_transaction', $transaction);
            break;
        default:
            $transaction = apply_filters($transaction->getOwner() . '_pay_channel_canceled_transaction', $transaction);
            break;
    }
    $transaction = PayChannelTransactionsRepository::update($transaction);
    if($transaction->getStatus() === PayChannelTransaction::STATUS_REFUNDS){
        $transaction = apply_filters('${channel}_pay_channel_pay_refunds', $transaction);
    }
    $transaction = PayChannelTransactionsRepository::update($transaction);
    #TODO: redirect to owner callback
}

function init_pay_channels(){

}

//add_action( 'init', 'init_pay_channels' );
add_shortcode( 'wp_pay_channels_channel_selection', 'wp_pay_channels_channel_selection' );
add_action( 'admin_post_pay_channels_pay_request', 'pay_channels_pay_request' );
add_action( 'admin_post_pay_channels_pay_answer', 'pay_channels_pay_answer' );

?>
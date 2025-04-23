<?php
namespace Softcarebd\Payment;
use Exception;

class PaymentClient{
    private $apiKey;
    private $apiBaseUrl = "https://tzsmmpay.com/api";

    /**
     * PaymentClient constructor.
     *
     * @param string $apiKey
     * @param string $apiBaseUrl
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Create a payment request.
     *
     * @param array $paymentData
     * @return PaymentResponse
     * @throws Exception
     */
    public function createPayment(array $paymentData): PaymentResponse
    {
        $url = "{$this->apiBaseUrl}/payment/create";

        $response = $this->makeRequest($url, $paymentData);

        if (isset($response['trx_id'])) {
            return new PaymentResponse(true, [
                'transaction_id' => $response['trx_id'],
                'payment_url' => "{$this->apiBaseUrl}/payment/{$response['trx_id']}"
            ]);
        }

        return new PaymentResponse(false, [], $response['messages'] ?? 'Unknown error occurred.');
    }

    /**
     * Verify a payment.
     *
     * @param string $transactionId
     * @return PaymentResponse
     * @throws Exception
     */
    public function verifyPayment(string $transactionId): PaymentResponse
    {
        $url = "{$this->apiBaseUrl}/payment/verify";
        $data['trx_id'] = $transactionId;
        $response = $this->makeRequest($url, $data);

        if (isset($response['success']) && $response['success'] == true) {
            return new PaymentResponse(true, $response);
        }

        return new PaymentResponse(false, [], $response['messages'] ?? 'Verification failed.');
    }


    /**
     * Make an HTTP request to the API.
     *
     * @param string $url
     * @param array|null $postData
     * @return array
     * @throws Exception
     */
    private function makeRequest(string $url, array $postData = null): array
    {
        // Add the API key to the post data
        if ($postData) {
            $postData['api_key'] = $this->apiKey;
        } else {
            $postData = ['api_key' => $this->apiKey];
        }

        $curl = curl_init();

        // Set up the cURL options
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true, // Enable POST request
            CURLOPT_POSTFIELDS => http_build_query($postData), // Send POST data
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded', // Ensure form data format
            ],
        ];

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new Exception("cURL Error: $error");
        }

        curl_close($curl);

        return json_decode($response, true);
    }
}
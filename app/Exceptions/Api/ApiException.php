<?php

namespace App\Exceptions\Api;

use Illuminate\Support\Facades\Log;

class ApiException extends \Exception
{
    private $headers;
    private $statusCode;
    private $context;
    protected $message;

    /**
     * @param string $message 错误信息
     * @param int $statusCode HTTP状态码
     * @param int|null $code  错误码
     * @param array $context  触发异常时的数据上下文，一般是方法或函数运行时的参数信息。
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message, int $statusCode = 200, ?int $code = 1, array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->message = $message;
        $this->headers = [];
        $this->statusCode = $statusCode;
        $this->context = $context;
    }

    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    public function setContext($context)
    {
        $this->context = $context;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContext(): string
    {
        return \json_encode($this->context);
    }

    public function report()
    {
        Log::info(sprintf("message: %s, context: %s\n%s", $this->message, $this->getContext(), $this->getTraceAsString()));
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        $response = response()->json([
            'code' => $this->getCode(),
            'message' => $this->getMessage()
        ]);
        $response->setStatusCode($this->getStatusCode());
        if ($this->headers) {
            $response->withHeaders($this->headers);
        }
        return $response;
    }
}

<?php

namespace App\Exceptions;

use App\Http\Controllers\ApiController;
use App\Traits\ApiResponder;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;


class Handler extends ExceptionHandler
{
    use ApiResponder;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Validation\ValidationException::class
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if($exception instanceOf ValidationException){
            return $this->convertValidationExceptionToResponse($exception, $request);
        }

        if($exception instanceOf ModelNotFoundException){
            $modelName = strtolower(class_basename($exception->getModel()));
            return $this->errorResponse("No {$modelName} instance exists with the specified model id.", 404);
        }

        if($exception instanceOf AuthenticationException){
            return $this->unauthenticated($request, $exception);
        }

        if($exception instanceOf AuthorizationException){
            return $this->errorResponse($exception->getMessage(), 403);
        }

        if($exception instanceOf NotFoundHttpException){
            return $this->errorResponse('The specified URL could not be found', 404);
        }

        if($exception instanceOf MethodNotAllowedHttpException){
            return $this->errorResponse('The specified method for the request is invalid.', 405);
        }

        if($exception instanceOf HttpException){
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        }

        if($exception instanceOf QueryException){
            $errorCode = $exception->errorInfo[1];

            if($errorCode == 1451){

                return $this->errorResponse('Cannot remove this resource permanently as it is related to another resource.', 409);
            }
        }

        if(config('app.debug')){
            return parent::render($request, $exception);
        }
        // place all other general exceptions here
        return $this->errorResponse('Unexpected error. Please try again later.', 500);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $this->errorResponse('Unauthenticated.', 401);
    }

    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {

        $errors = $e->validator->errors()->getMessages();

        return $this->errorResponse($errors, 422);

    }
}

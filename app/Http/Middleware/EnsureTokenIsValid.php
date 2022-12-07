<?php

namespace App\Http\Middleware;

use App\Services\ConfigurationProvider\ConfigurationProvider;
use Carbon\Carbon;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class EnsureTokenIsValid
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $configs = ConfigurationProvider::getConfig();
        if(!$request->header('ProjectId')){
            throw new BadRequestHttpException("Unable to complete your request",);
        }

        $projectId = $request->header('ProjectId');
        $found = null;

        foreach($configs as $config){
            if($config['projectId'] === $projectId){
                $found = $config;
            }
        }

        if(!$found){
            return response(null, Response::HTTP_UNAUTHORIZED);
        }

        $availableToAccess = true;
        $protectedBy = $found['protected-by'];
        if(isset($protectedBy['token'])){
            $timestampHeader = $request->header('X-Timestamp');
            $keyHeader = $request->header("X-Authorization");

            if(!$timestampHeader || !$keyHeader){
                throw new BadRequestHttpException("Unable to complete your request");
            }

            $timestamp = Carbon::createFromTimestamp($timestampHeader);

            if(Carbon::now()->diffInSeconds($timestamp) > $protectedBy['token']['interval']){
                throw new BadRequestHttpException("Unable to complete your request");
            }

            $encodedHash = sha1($protectedBy['token']['token'].$timestampHeader);

            if($encodedHash !== $keyHeader){
                $availableToAccess = false;
            }
        }

        if(!$availableToAccess){
            return response(null, Response::HTTP_UNAUTHORIZED);
        }


        return $next($request);
    }
}

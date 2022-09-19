<?php

  namespace App\Http\Middleware;
  
  use Closure;
//   use App;
//   use Illuminate\Foundation\Application;
// 	use Illuminate\Http\Request;
// 	use Illuminate\Routing\Redirector;
	// use Illuminate\Support\Facades\App;
	// use Illuminate\Support\Facades\Config;
	// use Illuminate\Support\Facades\Session;
  
	class Localization{
		/**
		 * Handle an incoming request.
		 *
		 * @param  \Illuminate\Http\Request  $request
		 * @param  \Closure  $next
		 * @return mixed
		 */
		public function handle($request, Closure $next){
			$local = (($request->hasHeader('X-localization')) ? $request->header('X-localization') : 'en');

			app('translator')->setLocale($local);

			return $next($request);
		}
	}
  

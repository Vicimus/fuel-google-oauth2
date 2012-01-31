<?php

namespace Google;

/**
 * Google API class
 *
 * This class is (the start of) an OAuth2 implementation of the fuel-google package (https://github.com/ninjarite/fuel-google)
 *
 * 
 * @author   Rob McCann
 * @version  1.0
 * @package  Fuel
 * @package  Google
 * @category classes
 */
abstract class GoogleAPI {
	/* OAuth tokens */
	protected $access_token = null;
	protected $refresh_token = null;	
	protected $expires = null;
	
	/* App keys */
	protected $client_id = null;
	protected $client_secret = null;	
	
	protected static $instance = null;
	
	public static function forge(array $config = array())
	{
		static::$instance = new static();
		
		if (isset($config['tokens']))
		{
			static::$instance->set_tokens($config['tokens']);
		}
		
		if (isset($config['client']))
		{
			static::$instance->set_client($config['client']);
		}
		return static::$instance;
	}
	
	public function set_tokens(array $tokens)
	{
		if (isset($tokens['access_token']))
		{
			$this->set_access_token($tokens['access_token']);
		}
		
		if (isset($tokens['refresh_token']))
		{
			$this->set_refresh_token($tokens['refresh_token']);
		}
		
		if (isset($tokens['expires']))
		{
			$this->set_expires($tokens['expires']);
		}
	}
	
	public function set_access_token($token)
	{
		$this->access_token = $token;
		return $this;
	}
	
	public function set_refresh_token($token)
	{
		$this->refresh_token = $token;
		return $this;
	}
	
	public function set_expires($expires)
	{
		$this->expires = $expires;
		return $this;
	}
	
	public function set_client(array $config)
	{
		if (isset($config['id']))
		{
			$this->set_client_id($config['id']);
		}
		
		if (isset($config['secret']))
		{
			$this->set_client_secret($config['secret']);
		}
		
		return $this;
	}
	
	public function set_client_id($id)
	{
		$this->client_id = $id;
		return $this;
	}
	
	public function set_client_secret($key)
	{
		$this->client_secret = $key;
		return $this;
	}
	
	public function refresh_token($callback = null)
	{
		if ( ! $this->refresh_token)
		{
			throw new \FuelException('The refresh token was not supplied or was empty');
		}
		
		if ( ! $this->client_id or ! $this->client_secret)
		{
			throw new \FuelException('The client_id and client_secret are required to refresh tokens');
		}
		
		if ( \Package::load('oauth2') === false)
		{
			throw new \FuelException('The OAuth2 package is required to refresh tokens');
		}
		
		$access_token = \OAuth2\Provider::forge('google', array(
		  'id' => $this->client_id,
		  'secret' => $this->client_secret,
		))->access($this->refresh_token, array('grant_type' => 'refresh_token'));
		
		print_r($access_token);
		exit;

		/*
		TODO find a way to store the new token, perhaps a callback that's defined in the forge config
		that gets called here. Observer pattern would probably be the proper way of doing it. 
		*/
		
		//this is called when the 		
		if (is_callable($callback))
		{
			$callback($this);
		}
		
		return $access_token;
	}
	
	public function get($url, array $params = array())
	{
		return $this->call($url, 'get', $params);
	}
	
	public function post($url, array $params = array())
	{
		return $this->call($url, 'post', $params);
	}
	
	public function call($url, $method = 'get', array $params = array(), $is_refreshed = false)
	{
		if ( ! $this->access_token )
		{
			throw new \FuelException('Please provide your google access token');
		}
		
		$curl = \Request::forge('https://www.googleapis.com/'.$url, array(
			'driver' => 'curl',
			'method' => strtolower($method),
		))->set_header('Authorization', 'Bearer '.$this->access_token);
		
		try
		{
			$response = $curl->execute()->response();
			
			$debug = new \stdClass;
			$debug->error = new \stdClass;
			$debug->error->code = 401;
			throw new \RequestStatusException(json_encode($debug),401);
			if (intval($response->status / 100) != 2) 
			{
				throw new \FuelException('There was a problem contacting the Google API ('.$response->status.')');
			}
		}
		catch (\RequestStatusException $e)
		{
			$response = json_decode($e->getMessage());
			switch ($response->error->code)
			{
				case 401:
					//is_refreshed stops an unending loop if the token is actually invalid and not just expired
					if ( ! $is_refreshed)
					{
						try
						{
							$this->refresh_token(function($api) use ($url, $method, $params){
								$api->call($url, $method, $params, true);
							});
						}
						catch (\Exception $refresh_e)
						{
							//it failed its second attempt or there was a problem with the second attempt
							throw $e;
						}
					}
					else
					{
						throw $e;
					}
					break;
				default:
					throw $e;
			}
		}
		
		return json_decode($response->body);
		
	}

}
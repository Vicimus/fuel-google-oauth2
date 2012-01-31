<?php

namespace Google;

class Analytics extends GoogleAPI {

	/**
	 * Retrieve the list of accounts according to your Analytics account
	 *
	 * @return  array
	 */
	public function get_accounts()
	{
		// make the call to the API
		$response = $this->get('analytics/v3/management/accounts');
		$accounts = array();

		//parse the response from the API using DOMDocument.
		if ($response)
		{
			foreach ($response->items as $item)
			{
				$accounts[] = array(
					'id' => $item->id,
					'name' => $item->name,
					'updated_at' => strtotime($item->updated),
					'created_at' => strtotime($item->created),
					
				);
			}			
		}
		else
		{
			throw new \Exception('get_website_profiles() failed to get a response from Google Analytics API service');
		}
		
		return $accounts;
	}
	
	/**
	 * Retrieve the list of web properties for an account or all web properties
	 *
	 * @return  array
	 */
	public function get_properties($account = '~all')
	{
		// make the call to the API
		$response = $this->get("analytics/v3/management/accounts/$account/webproperties");
		$properties = array();

		//parse the response from the API using DOMDocument.
		if ($response)
		{
			foreach ($response->items as $item)
			{
				$properties[] = array(
					'id' => $item->id,
					'name' => $item->name,
					'account_id' => $item->accountId,
					'website_url' => $item->websiteUrl,
					'updated_at' => strtotime($item->updated),
					'created_at' => strtotime($item->created),
					
				);
			}			
		}
		else
		{
			throw new \Exception('get_website_properties() failed to get a response from Google Analytics API service');
		}
		
		return $properties;
	}

	/**
	 * Returns an HTML snippet to allow tracking of a page
	 *
	 * @return  string
	 */
	public function track($web_profile_id = '')
	{
		if(empty($web_profile_id))
		{
			$web_profile_id = \Config::get('analytics.web_profile_id');

			if(empty($web_profile_id))
			{
				throw new \Exception('Please define your Google Analytics web profile id (UA-XXXXX-X) inside of your analytics configuration file.');
			}
		}

		return(
			'<script>' . PHP_EOL .
			'var _gaq=[["_setAccount","' . $web_profile_id . '"],["_trackPageview"]];' . PHP_EOL .
			'(function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];g.async=1;' . PHP_EOL .
			'g.src=("https:"==location.protocol?"//ssl":"//www")+".google-analytics.com/ga.js";' . PHP_EOL .
			's.parentNode.insertBefore(g,s)}(document,"script"));' . PHP_EOL .
			'</script>' . PHP_EOL
		);
	}
}

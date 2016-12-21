<?php

function predictWhenTakeover($blocka, $blockb)
{
	if ($blocka['production'] == 0)
	{
		return 1000;
	}
	else
	{
		return ceil(($blockb['strength'] - $blocka['strength']) / $blocka['production']);
	}
}

function findTimes($block)
{
	$howlong = [];
	if (isset($block['options']))
	{
		foreach ($block['options'] as $key => $option)
		{
			$compare = $block;
			$compare['strength'] = 0;
			if (isset($option['options']))
			{
				$howlong[$key] = array(
					"value"=> predictWhenTakeover($compare,$option), 
					"children" => findTimes($option)
				);
			}
			else
			{

				$howlong[$key] = array("value"=>predictWhenTakeover($compare,$option));
			}
		}
	}

	return $howlong;
}

function findBest($time)
{
	if (isset($time['children']) && count($time['children']) > 0)
	{
		$response = [];
		$values = [];
		foreach ($time['children'] as $key=>$child)
		{
			$response[$key] = findBest($child);
			$values[$key] = $response[$key]["value"];
		}
		$direction = array_search(min($values),$values);
		if (!isset($response[$direction]['path']))
		{
			$response[$direction]['path'] = array($direction);
		}
		else
		{
			array_unshift($response[$direction]['path'],$direction);
		}
		$response[$direction]['value'] += $time['value'];
		return $response[$direction];
	}
	else
	{
		return array("value"=>$time['value']);
	}
}

































//echo predictTakeOverValue(20, $myBlock, $block2);


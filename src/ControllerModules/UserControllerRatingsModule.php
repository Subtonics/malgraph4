<?php
class UserControllerRatingsModule extends AbstractUserControllerModule
{
	public static function getText(ViewContext $viewContext, $media)
	{
		return 'Ratings';
	}

	public static function getUrlParts()
	{
		return ['rati', 'rating', 'ratings'];
	}

	public static function getMediaAvailability()
	{
		return [Media::Anime, Media::Manga];
	}

	public static function getOrder()
	{
		return 2;
	}

	public static function work(&$viewContext)
	{
		$viewContext->viewName = 'user-ratings';
		$viewContext->meta->title = 'MALgraph - ' . $viewContext->user->name . ' - rating statistics (' . Media::toString($viewContext->media) . ')';
		$viewContext->meta->description = $viewContext->user->name . '&rsquo;s ' . Media::toString($viewContext->media) . ' rating statistics on MALgraph, an online tool that extends your MyAnimeList profile.';
		$viewContext->meta->keywords = array_merge($viewContext->meta->keywords, ['profile', 'list', 'achievements', 'ratings', 'activity', 'favorites', 'suggestions', 'recommendations']);
		$viewContext->meta->styles []= '/media/css/infobox.css';
		$viewContext->meta->styles []= '/media/css/user/ratings.css';
		$viewContext->meta->scripts []= 'http://code.highcharts.com/highcharts.js';
		$viewContext->meta->scripts []= '/media/js/highcharts-mg.js';
		$viewContext->meta->scripts []= '/media/js/user/entries.js';

		$list = $viewContext->user->getMixedUserMedia($viewContext->media);
		$list = array_filter($list, function($mixedUserMedia) {
			return $mixedUserMedia->status != UserListStatus::Planned;
		});
		$viewContext->ratingDistribution = RatingDistribution::fromEntries($list);
		$viewContext->ratingTimeDistribution = RatingTimeDistribution::fromEntries($list);
		$listNoMovies = array_filter($list, function($mixedUserMedia) {
			return !($mixedUserMedia->sub_type == AnimeMediaType::Movie and $mixedUserMedia->media == Media::Anime);
		});
		$viewContext->lengthDistribution = MediaLengthDistribution::fromEntries($listNoMovies);

		list($year, $month, $day) = explode('-', $viewContext->user->join_date);
		$earliest = mktime(0, 0, 0, $month, $day, $year);
		$totalTime = 0;
		foreach ($list as $mixedUserMedia)
		{
			$totalTime += $mixedUserMedia->completed_duration;
			foreach ([$mixedUserMedia->start_date, $mixedUserMedia->end_date] as $k)
			{
				$f = explode('-', $k);
				if (count($f) != 3) {
					continue;
				}
				$year = intval($f[0]);
				$month = intval($f[1]);
				$day = intval($f[2]);
				if (!$year or !$month or !$day)
				{
					continue;
				}
				$time = mktime(0, 0, 0, $month, $day, $year);
				if ($time < $earliest) {
					$earliest = $time;
				}
			}
		}

		$viewContext->earliestTimeKnown = $earliest;
		$viewContext->meanTime = $totalTime / max(1, (time() - $earliest) / (24. * 3600.0));
	}
}
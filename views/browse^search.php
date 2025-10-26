<?php
// class to combine SQL queries dependent on data provided and return results from media table 
class Search {
	private string $query = '
		SELECT id, user_id, status, name, image
		FROM media
		WHERE %criteria%
		%limit%
	';
	private array $criteria = ['private=0', 'deleted=0'];
	private array $values = [];
	private int $lower_limit;
	private int $upper_limit;

	function __construct( ){
		
	}

	function set_limit( int|null $min = 1, int|null $max = null ){
		if( $min !== null ){
			$this->lower_limit = $min;
		}
		if( $max !== null ){
			$this->upper_limit = $max;
		}
	}

	// convert a user-readable string query into internal criteria instructions
	function parse_criteria( $query ){
		# add whitespace to start and end for regex matching purposes.
		$query = ' '.trim($query).' ';

		$start_matches = [];
		# TODO: this does nothing right now.
		# improve this with more advanced date matching, such as 2024-06-25 and 2024-jun-25
		# maybe 'today' and other more natural phrases
		# and operators such as > < = etc
		preg_match( '/\s+start(?:ed)?:([^\s]+)\s+/i', $query, $start_matches );
		if( count($start_matches) > 1 ){
			$start = $start_matches[1];
		}
	}

	// push items to the array as needed
	function add_criteria(
		string|null $name = null,
		int|null $min_year = null,
		int|null $max_year = null,
		array|null $links = null,
		bool $only_own = false,
		bool $exclude_own = false,
		bool|null $adult = null
	){
		if( $name !== null && strlen($name) > 0 ){
			array_push($this->criteria, 'name LIKE ?');
			array_push($this->values, '%'.$name.'%');
		}
	}

	private function calculate_types( ): string {
		// parse types
		$value_types = '';
		foreach( $this->values as $val ){
			if( is_numeric($val) === 'true' ){
				$value_types .= 'i';
			}
			$value_types .= 's';
		}
		return $value_types;
	}

	// Prepares the query for use in an execution
	private function prep_query( $query ): string {
		// parse limit
		$limit = '';
		if( isset($this->upper_limit) ){
			$limit .= 'LIMIT '.$this->upper_limit.' ';
		}
		if( isset($this->lower_limit) ){
			$limit .= 'OFFSET '.$this->lower_limit;
		}

		// perform replacements
		$query = str_replace('%criteria%', implode(' AND ', $this->criteria), $query);
		$query = str_replace('%limit%', $limit, $query);

		return $query;
	}

	function get_total( ): int {
		$query = $this->prep_query(str_replace('%limit%', '', $this->query));

		$stmt = sql( $this->prep_query($this->query), [$this->calculate_types(), ...$this->values] );
		return $stmt->row_count;
	}

	function execute( ): SqlResult {
		return sql( $this->prep_query($this->query), [$this->calculate_types(), ...$this->values] );
	}
}

$search = new Search();
if( isset($_GET['q']) ){
	$search->parse_criteria($_GET['q']);
	$total = $search->get_total();
}
else {
	$total = 0;
}

$pagination = new Pagination();
$pagination->Setup(30, $total);
$search->set_limit($pagination->offset, $pagination->increment);

$search_result = $search->execute();
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<div class="content-header">
			<div class="content-header__breadcrumb">
				<a href="/browse">Browse</a> >
				<span>Search</span>
			</div>
			<h2 class="content-header__title">Search <?=isset($_GET['q']) === 0 ? 'Results' : ''?></h2>
		</div>

		<div class="l-leave-a-gap">
			<div class="c-search">
				<div class="c-search__bar js-search-bar">
					<input class="c-search__input js-autofill" type="search" autocomplete="off" placeholder="Search for Movies, Games, TV, Books, Anime, and more..." data-autofill="<?=$_GET['q']?>">
					<button class="button c-search__submit" type="button">Search</button>
				</div>
			</div>
		</div>

		<?php if( $pagination->total > $pagination->increment ) : ?>
		<div class="page-actions l-leave-a-gap">
			<?php $pagination->Generate(); ?>
		</div>
		<?php endif; ?>

		<?php if( $total === 0 ) : ?>

		<div>No Results</div>

		<?php else : ?>

		<div class="l-card-layout l-leave-a-gap">
			<?php
			if( $search_result->row_count < 1 ){
				echo 'No results.';
			} else {
				$results = $search_result->rows;
				foreach( $results as $module_media ){
					include(PATH.'modules/media_card.php');
				}
			}
			?>
		</div>

		<?php endif; ?>
	</div>
</main>
<?php declare(strict_types=1);
class Pagination {
	public $offset;
	public $increment;
	public $total;

	public function Pagination() {
		$this->offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
		$this->increment = 20;
		$this->total = 0;
	}

	public function Setup($increment, $total) {
		$this->increment = $increment;
		$this->total = $total;
	}

	public function Generate() {
		if( $this->total <= $this->increment ){
			return false;
		}

		// Get page count
		$pages = $this->increment === 0 ? 0 : ceil($this->total / $this->increment);
		// Replaces all "page=#" from URL query
		$normalized_query = preg_replace("/\&offset\=.+?(?=(\&|$))/", "", $_SERVER['QUERY_STRING']);

		// Begin HTML
		echo '<div class="page-actions__pagination">Page:';
				
		if( $pages < 8 ){
			$i = 0;
			while($i < $pages) {
				$o = $i * $this->increment;
				$i++;
				echo ' <a class="page-actions__pagination-link" href="?'.$normalized_query.'&offset='.$o.'">'.$i.'</a>';
			}
		}
		else {
			// Always displays first two and last two pages plus the closest pages to the user
			$current_page = ceil($this->offset / $this->increment) + 1;
			$pages_to_display = [1, $current_page, $pages];
			$nearby_pages = [$current_page - 2, $current_page - 1, $current_page + 1, $current_page + 2];
			foreach( $nearby_pages as $p ){
				if( $p > 1 && $p < $pages ){
					array_push($pages_to_display, $p);
				}
			}

			$pages_to_display = array_unique($pages_to_display, SORT_NUMERIC);
			sort($pages_to_display);
			
			$previous_page = 0;
			
			foreach( $pages_to_display as $page ){
				$offset = ($page - 1) * $this->increment;

				if( $page - 1 != $previous_page ){
					echo ' … ';
				}
				
				echo ' <a class="page-actions__pagination-link" href="?'.$normalized_query.'&offset='.$offset.'">'.$page.'</a>';

				$previous_page = $page;
			}
		}
		
		echo '</div>';
		// End HTML

		return true;
	}
}
?>
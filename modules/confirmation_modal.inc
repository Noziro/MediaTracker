<div id="modal--confirmation" class="modal modal--hidden" role="dialog" aria-modal="true">
	<button class="modal__background" onclick="toggleModal('modal--confirmation', false)"></button>
	<div class="modal__inner">
		<h3 id="js-confirmation-msg" class="modal__header"></h3>
		<div class="js-confirmation-preview"><!-- TODO - unused atm - plan to put post content here to display what user is deleting --></div>
		<form id="form-confirmation" action="/interface" method="POST" style="display:none">
			<input type="hidden" name="return_to" value="<?=$_SERVER['REQUEST_URI']?>">
			<input id="js-confirmation-data" type="hidden">
		</form>
		<div class="l-button-list">
			<button form="form-confirmation" class="l-button-list__button button button--medium button--negative js-modal-focus" type="submit">Confirm</a>
			<button class="l-button-list__button button button--medium" onclick="toggleModal('modal--confirmation', false)">Cancel</a>
		</div>
	</div>
</div>
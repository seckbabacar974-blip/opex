
// TAB 2 JS
if(php_data.active_tab == 'tab-1') {
	let society_table = document.querySelector('input[name="mlw_society_name"]');
	society_table = society_table.closest('table')

	let society_h2 = society_table.previousElementSibling;
	
	let personnal_table = document.querySelector('input[name="mlw_personnal_first_and_last_name"]');
	personnal_table = personnal_table.closest('table')

	let personnal_h2 = personnal_table.previousElementSibling;

	set_visibility_of_society_and_personnal();

	document.querySelector('input[name="mlw_is_society"]').addEventListener('change', set_visibility_of_society_and_personnal);


	function set_visibility_of_society_and_personnal() {
		let toggle_value = document.querySelector('input[name="mlw_is_society"]').checked;
		if(toggle_value) {
			society_table.style.display = 'table';
			society_h2.style.display = 'block';
			personnal_table.style.display = 'none';
			personnal_h2.style.display = 'none';
		} else {
			society_table.style.display = 'none';
			society_h2.style.display = 'none';
			personnal_table.style.display = 'table';
			personnal_h2.style.display = 'block';
		}
	}
}

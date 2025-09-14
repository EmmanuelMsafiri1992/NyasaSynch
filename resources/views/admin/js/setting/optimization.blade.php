<script>
	onDocumentReady((event) => {
		let driverEl = document.querySelector("select[name=cache_driver].select2_from_array");
		if (driverEl) {
			getDriverFields(driverEl);
			$(driverEl).on("change", e => getDriverFields(e.target));
		}
	});
	
	function getDriverFields(driverEl) {
		setElementsVisibility("hide", ".memcached");
		if (driverEl.value === "memcached") {
			setElementsVisibility("show", ".memcached");
		}
	}
</script>

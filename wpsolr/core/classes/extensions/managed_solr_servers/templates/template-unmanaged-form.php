<?php
/**
 * Unmanaged Solr server form
 */
?>

<div id="solr-configuration-tab" class="wdm-vertical-tabs-content">
	<div class='wrapper'>
		<h4 class='head_div'>Solr Configuration</h4>

		<div class="wdm_note">

			WPSOLR is compatible with the Solr versions listed at the following page: <a
				href="<?php echo $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/kb/apache-solr/apache-solr-configuration-files/' ); ?>" target="__wpsolr">Compatible Solr versions</a>.

			Your first action must be to download the two configuration files (schema.xml,
			solrconfig.xml) listed in the online release section, and upload them to your Solr instance.
			Everything is described online.

		</div>
		<div class="wdm_row">
			<div class="submit">
				<a href='admin.php?page=solr_settings&tab=solr_indexes' class="button-primary wdm-save">I
					uploaded my 2 compatible configuration files to my Solr core >></a>
			</div>
		</div>
	</div>
</div>
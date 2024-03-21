<table class="form-table">
	<tbody>
	<tr>
		<td id="user-export-count">
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="user-exports">
				<?php _e('Export Users');?>
			</label>
		</th>
		<td>
			<button id="user-exports" type="submit" class="button button-primary">Export Users</button>
		</td>
	</tr>
	</tbody>
</table>
<p class="description">Export all subscriber users to a CSV, that can then be downloaded </p>
<table class="form-table">
    <tbody>
    <tr>
        <th scope="row">
            <label for="user-exports">
				<?php _e('Download Exported Users');?>
            </label>
        </th>
        <td>
            <form id="export-download">
                <input id="export-filename-id" name="export-filename" hidden/>
                <button id="download-user-exports" type="submit" disabled="disabled" class="button button-primary">Download</button>
            </form>
        </td>
    </tr>
    </tbody>
</table>

<table class="form-table">
    <tbody>
    <tr>
        <th scope="row">
            <label for="user-delete-uploads">
				<?php _e( 'Delete Users' ); ?>
            </label>
        </th>
        <td>
            <form id="delete-users">
                <input type="file" id="avatar" name="user-delete" accept=".csv"/>
                <br>
                <br>
                <button id="user-exports" type="submit" class="button button-primary">Export Users</button>
            </form>
        </td>
    </tr>
    </tbody>
</table>
<p class="description">Upload a CSV list of users to be deleted from the database </p>
<!--<table class="form-table">-->
<!--	<tbody>-->
<!--	<tr>-->
<!--		<th scope="row">-->
<!--			<label for="delete-users">-->
<!--				--><?php //_e('Delete Uploaded Users');?>
<!--			</label>-->
<!--		</th>-->
<!--		<td>-->
<!--			<form id="delete-users">-->
<!--				<button id="delete-uploaded-users" type="submit" disabled="disabled" class="button button-primary">Delete</button>-->
<!--			</form>-->
<!--		</td>-->
<!--	</tr>-->
<!--	</tbody>-->
<!--</table>-->

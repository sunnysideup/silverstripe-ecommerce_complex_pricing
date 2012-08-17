<div id="Sidebar">
	<div class="sidebarTop"></div>
	<% include Sidebar_Cart %>
	<div class="sidebarBottom"></div>
</div>
<div id="ProductGroup" class="mainSection">
	<h1 id="PageTitle">$Title</h1>
	<% if Content %><div id="ContentHolder">$Content</div><% end_if %>

<% if Products %>
	<div id="PriceList" class="category">
		<div class="resultsBar">
			<% if SortLinks %><span class="sortOptions"><% _t('ProductGroup.SORTBY','Sort by') %> <% control SortLinks %><a href="$Link" class="sortlink $Current">$Name</a> <% end_control %></span><% end_if %>
		</div>
		<table summary="price list">
			<thead></thead>
			<tfoot></tfoot>
			<tbody>
				<% control Products %>
				<tr>
					<th scope="row" class="$EvenOdd">
						<div class="inner">
							<a href="$Link">$Title</a>
						</div>
					</th>
					<td class="price">
						<div class="inner">
							$CalculatedPrice.Nice
							<% include Order_DisplayPrice %>
						</div>
					</td>
				</tr>
				<% end_control %>
			</tbody>
		</table>
<% include ProductGroupPagination %>
	</div>
<% end_if %>
	<% if Form %><div id="FormHolder">$Form</div><% end_if %>
	<% if PageComments %><div id="PageCommentsHolder">$PageComments</div><% end_if %>
</div>






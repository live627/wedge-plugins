function weStatsCenter(oOptions)
{
	this.oTable = $('#stats_history');

	// Is the table actually present?
	if (!this.oTable.length)
		return;

	this.opt = oOptions;
	this.oYears = {};
	this.bIsLoading = false;

	// Find all months and years defined in the table.
	var aResults = [], sYearId = null, oCurYear = null, sMonthId = null, oCurMonth = null, i, that = this;

	$('tr', this.oTable).each(function () {
		// Check if the current row represents a year.
		if ((aResults = oOptions.reYearPattern.exec(this.id)) != null)
		{
			// The id is part of the pattern match.
			sYearId = aResults[1];

			// Setup the object that'll have the state information of the year.
			that.oYears[sYearId] = {
				oCollapseImage: document.getElementById(oOptions.sYearImageIdPrefix + sYearId),
				oMonths: {}
			};

			// Create a shortcut, makes things more readable.
			oCurYear = that.oYears[sYearId];

			// Use the collapse image to determine the current state.
			oCurYear.bIsCollapsed = !$(oCurYear.oCollapseImage).hasClass('fold');

			// Setup the toggle element for the year.
			oCurYear.oToggle = new weToggle({
				bCurrentlyCollapsed: oCurYear.bIsCollapsed,
				sYearId: sYearId,
				funcOnBeforeCollapse: function () {
					that.onBeforeCollapseYear(this);
				},
				aSwappableContainers: [],
				aSwapImages: [
					{
						sId: oOptions.sYearImageIdPrefix + sYearId,
						altExpanded: '-',
						altCollapsed: '+'
					}
				],
				aSwapLinks: [
					{
						sId: oOptions.sYearLinkIdPrefix + sYearId,
						msgExpanded: sYearId
					}
				]
			});
		}

		// Or maybe the current row represents a month.
		else if ((aResults = oOptions.reMonthPattern.exec(this.id)) != null)
		{
			// Set the id to the matched pattern.
			sMonthId = aResults[1];

			// Initialize the month as a child object of the year.
			oCurYear.oMonths[sMonthId] = {
				oCollapseImage: document.getElementById(oOptions.sMonthImageIdPrefix + sMonthId)
			};

			// Create a shortcut to the current month.
			oCurMonth = oCurYear.oMonths[sMonthId];

			// Determine whether the month is currently collapsed or expanded..
			oCurMonth.bIsCollapsed = !$(oCurMonth.oCollapseImage).hasClass('fold');

			var sLinkText = $('#' + oOptions.sMonthLinkIdPrefix + sMonthId).html();

			console.log(sMonthId);

			// Setup the toggle element for the month.
			oCurMonth.oToggle = new weToggle({
				bCurrentlyCollapsed: oCurMonth.bIsCollapsed,
				sMonthId: sMonthId,
				aSwappableContainers: [],
				aSwapImages: [
					{
						sId: oOptions.sMonthImageIdPrefix + sMonthId,
						altExpanded: '-',
						altCollapsed: '+'
					}
				],
				aSwapLinks: [
					{
						sId: oOptions.sMonthLinkIdPrefix + sMonthId,
						msgExpanded: sLinkText
					}
				]
			});

			oCurYear.oToggle.opt.aSwappableContainers.push(this.id);
		}

		else if ((aResults = oOptions.reDayPattern.exec(this.id)) != null)
		{
			oCurMonth.oToggle.opt.aSwappableContainers.push(this.id);
			oCurYear.oToggle.opt.aSwappableContainers.push(this.id);
		}
	});

	// Collapse all collapsed years!
	for (i = 0; i < oOptions.aCollapsedYears.length; i++)
		this.oYears[this.opt.aCollapsedYears[i]].oToggle.toggle();
}

weStatsCenter.prototype.onBeforeCollapseYear = function (oToggle)
{
	// Tell Wedge that all underlying months have disappeared.
	var oMon = this.oYears[oToggle.opt.sYearId].oMonths, m = oMon.length, i;
	for (i = 0; i < m; i++)
		if (oMon[i].oToggle.opt.aSwappableContainers.length > 0)
			oMon[i].oToggle.cs(true);
};
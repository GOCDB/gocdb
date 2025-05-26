<span class="vSiteNotice">Please ensure the service administrators are aware you are adding their services to this service group.</span>

    <!--  Services -->
    <div class="listContainer">
        <span class="header listHeader">
            Search for Existing Services
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/grid.png" class="decoration" />
        <form action="javascript:void(0);">
            <input  class="input_input_text vSiteSearch" type="text"
                    name="Search" value="Search" onclick="clearText()"
                    id='filter' />
            <input
                class="vSiteSearchButton gocdb_btn gocdb_btn_props"
                type="submit"
                value="Search"
                onclick="startSearch()"
            />
        </form>


        <div class="vSiteSearchResultContainer" id="resultsContainer">
        <span class="header listHeader vSite1emBottomMargin">
            Results:
        </span>
        <br />
        <table class="vSiteResults" id="seTable">
            <tr class="site_table_row_1">
                <th style="width: 5em;" class="site_table">Add</th>
                <th style="width: 40%;" class="site_table">Service</th>
                <th class="site_table">Description</th>
                <th class="site_table">Hosting Site</th>
            </tr>
        </table>
        </div>
    </div>

    <!--  Selected Services -->
    <div class="listContainer">
        <span class="header listHeader">
            Services to Add
        </span>
        <img src="<?php echo \GocContextPath::getPath()?>img/grid.png" class="decoration" />
        <table class="vSiteResults" id="selectedSETable">
            <tr class="site_table_row_1">
                <th class="site_table">Remove</th>
                <th class="site_table">Service</th>
                <th class="site_table">Description</th>
                <th class="site_table">Hosting Site</th>
            </tr>
        </table>
    </div>

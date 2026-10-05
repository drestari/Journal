<script language="javascript" src="includes/validation.js"></script>
<script language="javascript">
function checkSearch(fsearch){
	if(fsearch.searchkey.value == ""){
		alert("Please Manuscript Title");
		fsearch.searchkey.focus();
		return false;
	}else return true;
}
</script>
<div class="manubox mar-b-30">
						<div class="content">
							<form name="fsearch" method="post" action="manuscripttitlesearch_results.php" onSubmit="return checkSearch(this);">
                                <div class="row">
                                    <div class="col-md-12 col-sm-12">
                                        <div class="form-group">
											<label>Find your manuscript</label>
                                            <input name="searchkey" value="" placeholder="Manuscript Title" id="searchkey" type="text" class="inputsty">
											<!--<span>Manuscript title / Author Name</span>-->
                                        </div>
                                    </div>
									
									<div class="col-md-12 col-sm-12">
									      <input name="button" class="btn btn-style-sixteen" type="submit"  id="button" value="Search" />
										<!--<button type="submit" class="btn btn-style-sixteen">Search</button-->
                                    </div>
                                </div>
                            </form>
						</div>
					</div>
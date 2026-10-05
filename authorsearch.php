<script language="javascript" src="includes/validation.js"></script>
<script language="javascript">
function checkSearch1(fsearch1){
	if(fsearch1.searchkey1.value == ""){
		alert("Please Manuscript Author Name");
		fsearch1.searchkey1.focus();
		return false;
	}else return true;
}
</script>
<div class="manubox mar-b-30">
						<div class="content">
							<form name="fsearch1" method="post" action="manuscripttitleauthorname_results.php" onSubmit="return checkSearch1(this);">
                                <div class="row">
									<div class="col-md-12 col-sm-12">
                                        <div class="form-group">
											<label>Track your manuscript status</label>
                                            <input name="searchkey1" value="" placeholder="Manuscript Author Name" id="searchkey1" type="text" class="inputsty">
											<!--<span>Manuscript title / Author Name</span>-->
                                        </div>
                                    </div>
                                    <div class="col-md-12 col-sm-12">
										<input name="button" class="btn btn-style-sixteen" type="submit"  id="button" value="Search" />
                                    </div>
                                </div>
                            </form>
						</div>
					</div>
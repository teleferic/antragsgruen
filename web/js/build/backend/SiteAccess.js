var SiteAccess=function(){function e(){this.initSite(),this.initUsers()}return e.prototype.initSite=function(){$("#siteSettingsForm").find(".loginMethods .namespaced input").change(function(){$(this).prop("checked")?$("#accountsForm").removeClass("hidden"):$("#accountsForm").addClass("hidden")}).trigger("change"),$(".removeAdmin").click(function(){var e=$(this),t=$(this).parents("form").first();bootbox.confirm(__t("admin","removeAdminConfirm"),function(i){if(i){var n=e.data("id");t.append('<input type="hidden" name="removeAdmin" value="'+n+'">'),t.submit()}})}),$(".managedUserAccounts input").change(function(){$(this).prop("checked")?$(".showManagedUsers").show():$(".showManagedUsers").hide()}).trigger("change")},e.prototype.initUsers=function(){$("#accountsCreateForm").submit(function(e){var t=$("#emailText").val();t.indexOf("%ACCOUNT%")==-1&&(bootbox.alert(__t("admin","emailMissingCode")),e.preventDefault()),t.indexOf("%LINK%")==-1&&(bootbox.alert(__t("admin","emailMissingLink")),e.preventDefault());var i=$("#emailAddresses").val().split("\n"),n=$("#names").val().split("\n");1==i.length&&""==i[0]&&(e.preventDefault(),bootbox.alert(__t("admin","emailMissingTo"))),i.length!=n.length&&(bootbox.alert(__t("admin","emailNumberMismatch")),e.preventDefault())}),$(".accountListTable .accessViewCol input[type=checkbox]").change(function(){$(this).prop("checked")||$(this).parents("tr").first().find(".accessCreateCol input[type=checkbox]").prop("checked",!1)}),$(".accountListTable .accessCreateCol input[type=checkbox]").change(function(){$(this).prop("checked")&&$(this).parents("tr").first().find(".accessViewCol input[type=checkbox]").prop("checked",!0)})},e}();new SiteAccess;
//# sourceMappingURL=SiteAccess.js.map

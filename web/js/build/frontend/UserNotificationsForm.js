define(["require","exports"],function(e,n){"use strict";Object.defineProperty(n,"__esModule",{value:!0});var t=function(){return function(e){this.$widget=e,$(".notiComment input").change(function(e){$(e.currentTarget).prop("checked")?$(".commentSettings").removeClass("hidden"):$(".commentSettings").addClass("hidden")}).trigger("change"),$(".notiAmendment input").change(function(e){$(e.currentTarget).prop("checked")?$(".amendmentSettings").removeClass("hidden"):$(".amendmentSettings").addClass("hidden")}).trigger("change")}}();n.UserNotificationsForm=t});
//# sourceMappingURL=UserNotificationsForm.js.map
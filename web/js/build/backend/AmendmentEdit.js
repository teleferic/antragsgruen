define(["require","exports","./MotionSupporterEdit","../shared/AntragsgruenEditor","../shared/AmendmentEditSinglePara","./MotionSupporterEdit"],function(t,e,i,n,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),new(function(){function t(){this.lang=$("html").attr("lang"),this.$editTextCaller=$("#amendmentTextEditCaller"),$("#amendmentDateCreationHolder").datetimepicker({locale:this.lang}),$("#amendmentDateResolutionHolder").datetimepicker({locale:this.lang}),this.$editTextCaller.find("button").click(this.textEditCalled.bind(this)),$(".amendmentDeleteForm").submit(function(t,e){if(!e||(e.confirmed,0)||!0!==e.confirmed){var i=$(this);t.preventDefault(),bootbox.confirm(__t("admin","delAmendmentConfirm"),function(t){t&&i.trigger("submit",{confirmed:!0})})}}),new i.MotionSupporterEdit($("#motionSupporterHolder"))}return t.prototype.textEditCalledMultiPara=function(){$(".wysiwyg-textarea").each(function(){var t=$(this),e=t.find(".texteditor"),i=new n.AntragsgruenEditor(e.attr("id")),a=i.getEditor();e.parents("form").submit(function(){e.parent().find("textarea.raw").val(a.getData()),void 0!==a.plugins.lite&&(a.plugins.lite.findPlugin(a).acceptAll(),e.parent().find("textarea.consolidated").val(a.getData()))})})},t.prototype.textEditCalled=function(){this.$editTextCaller.addClass("hidden"),$("#amendmentTextEditHolder").removeClass("hidden"),this.$editTextCaller.data("multiple-paragraphs")?this.textEditCalledMultiPara():new a.AmendmentEditSinglePara,$("#amendmentUpdateForm").append("<input type='hidden' name='edittext' value='1'>")},t}())});
//# sourceMappingURL=AmendmentEdit.js.map

/// <reference path="../typings/fuelux/index.d.ts" />

export class AppearanceEdit {
    constructor(private $form: JQuery) {
        this.initLogoUpload();
        this.initLayoutChooser();

        $('[data-toggle="tooltip"]').tooltip();
    }

    private initLogoUpload() {
        const $logoRow = this.$form.find('.logoRow'),
            $uploadLabel = $logoRow.find('.uploadCol label .text');
        $logoRow.on('click', '.imageChooserDd ul    a', ev => {
            ev.preventDefault();
            const src = $(ev.currentTarget).find("img").attr("src");
            $logoRow.find('input[name=consultationLogo]').val(src);
            if ($logoRow.find('.logoPreview img').length === 0) {
                $logoRow.find('.logoPreview').prepend('<img src="" alt="">');
            }
            $logoRow.find('.logoPreview img').attr('src', src).removeClass('hidden');
            $uploadLabel.text($uploadLabel.data('title'));
            $logoRow.find("input[type=file]").val('');
        });
        $logoRow.find("input[type=file]").on("change", () => {
            const path = ($logoRow.find("input[type=file]").val() as string).split('\\');
            const filename = path[path.length - 1];
            $logoRow.find('input[name=consultationLogo]').val('');
            $logoRow.find(".logoPreview img").addClass('hidden');
            $uploadLabel.text(filename);
        });
    }

    private initLayoutChooser() {
        const $inputs = this.$form.find(".thumbnailedLayoutSelector input");
        const $editLink = this.$form.find(".editThemeLink");
        const editLinkDefault = $editLink.attr("href");
        const onChange = () => {
            let $selected = $inputs.filter(":checked");
            if ($selected.length === 0) {
                $selected = $inputs.first();
            }
            $editLink.attr("href", editLinkDefault.replace(/DEFAULT/, $selected.val() as string));
        };
        $inputs.on("change", onChange);
        onChange();
    }
}

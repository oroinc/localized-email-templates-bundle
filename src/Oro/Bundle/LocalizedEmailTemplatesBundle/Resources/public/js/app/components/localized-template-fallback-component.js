define([
    'jquery',
    'underscore',
    'oroui/js/app/components/base/component'
], function($, _, BaseComponent) {
    'use strict';

    var LocalizedTemplateFallbackComponent = BaseComponent.extend({
        $sourceElement: null,

        options: {
            sourceId: null,
            targetId: null
        },

        /**
         * @inheritDoc
         */
        constructor: function LocalizedTemplateFallbackComponent() {
            LocalizedTemplateFallbackComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            LocalizedTemplateFallbackComponent.__super__.initialize.call(this, options);

            this.$sourceElement = $('#' + this.options.sourceId);
            this.$sourceElement.on('change.localized-template-fallback', _.bind(this.onChange, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$sourceElement.off('.localized-template-fallback');

            LocalizedTemplateFallbackComponent.__super__.dispose.call(this);
        },

        /**
         * Enable/disable controls depending on the "use parent localization"
         */
        onChange: function() {
            var $target = $('#' + this.options.targetId);
            var editor = typeof tinyMCE !== 'undefined' && tinyMCE.get(this.options.targetId);

            if (this.$sourceElement.is(':checked')) {
                $target.attr('disabled', 'disabled');

                if (editor) {
                    editor.setMode('readonly');
                    $(editor.editorContainer).addClass('disabled');
                    $(editor.editorContainer).append('<div class="disabled-overlay"></div>');
                }
            } else {
                $target.removeAttr('disabled');

                if (editor) {
                    editor.setMode('design');
                    $(editor.editorContainer).removeClass('disabled');
                    $(editor.editorContainer).children('.disabled-overlay').remove();
                }
            }
        }
    });

    return LocalizedTemplateFallbackComponent;
});

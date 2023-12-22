module.exports = {
    'presets': [
        [
            '@vue/app',
            {
                'useBuiltIns': 'entry'
            }
        ]
    ],
    'plugins': [
        [
            'transform-imports',
            {
                'vuetify': {
                    'transform': '/es5/components/${member}',
                    'preventFullImport': false
                }
            }
        ]
    ]
};

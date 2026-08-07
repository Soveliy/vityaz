module.exports = {
  extends: ['stylelint-config-standard-scss'],
  plugins: ['stylelint-order'],
  rules: {
    'alpha-value-notation': 'number',
    'at-rule-empty-line-before': null,
    'color-hex-length': 'long',
    'custom-property-pattern': null,
    'declaration-empty-line-before': null,
    'function-no-unknown': null,
    'import-notation': 'string',
    'order/order': [
      'custom-properties',
      'dollar-variables',
      {
        type: 'at-rule',
        name: 'include',
      },
      'declarations',
      'rules',
    ],
    'selector-class-pattern': null,
    'scss/dollar-variable-empty-line-before': null,
    'scss/dollar-variable-pattern': null,
    'value-keyword-case': [
      'lower',
      {
        ignoreKeywords: ['Arial', 'currentColor'],
      },
    ],
  },
};

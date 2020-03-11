/**
 * Internal dependencies
 */
const { TRANSLATION_FUNCTIONS } = require( '../utils' );

/**
 * Returns the text domain passed to the given translation function.
 *
 * @param {string} functionName Translation function name.
 * @param {Array} args Function arguments.
 * @return {undefined|*} Text domain argument.
 */
function getTextDomain( functionName, args ) {
	switch ( functionName ) {
		case '__':
			return args[ 1 ];
		case '_x':
			return args[ 2 ];
		case '_n':
			return args[ 3 ];
		case '_nx':
			return args[ 4 ];
		default:
			return undefined;
	}
}

module.exports = {
	meta: {
		type: 'problem',
		schema: [
			{
				type: 'object',
				properties: {
					allowDefault: {
						type: 'boolean',
						default: false,
					},
					allowedTextDomains: {
						type: 'array',
						items: {
							type: 'string',
						},
						uniqueItems: true,
					},
				},
				additionalProperties: false,
			},
		],
		messages: {
			invalidValue: "Invalid text domain '{{ textDomain }}'",
			invalidType: 'Text domain is not a string literal',
			unnecessaryDefault: 'Unnecessary default text domain',
			missing: 'Missing text domain',
			useAllowedValue:
				'Use one of the whitelisted text domains: {{ textDomains }}',
		},
		fixable: 'code',
	},
	create( context ) {
		const options = context.options[ 0 ] || {};
		const { allowDefault, allowedTextDomains = [] } = options;
		const canFixTextDomain = allowedTextDomains.length === 1;

		return {
			CallExpression( node ) {
				const { callee, arguments: args } = node;
				if ( ! TRANSLATION_FUNCTIONS.includes( callee.name ) ) {
					return;
				}

				const textDomain = getTextDomain( callee.name, args );

				if ( textDomain === undefined ) {
					if ( ! allowDefault ) {
						const lastArg = args[ args.length - 1 ];
						const addMissingTextDomain = ( fixer ) => {
							return fixer.insertTextAfter(
								lastArg,
								`, '${ allowedTextDomains[ 0 ] }'`
							);
						};

						context.report( {
							node,
							messageId: 'missing',
							fix: canFixTextDomain ? addMissingTextDomain : null,
						} );
					}
					return;
				}

				const { type, value, range } = textDomain;

				if ( type !== 'Literal' ) {
					context.report( {
						node,
						messageId: 'invalidType',
					} );
					return;
				}

				if ( ! allowedTextDomains.includes( value ) ) {
					const previousArgIndex = args.indexOf( textDomain ) - 1;
					const previousArg = args[ previousArgIndex ];

					if ( 'default' === value && allowDefault ) {
						const removeDefaultTextDomain = ( fixer ) => {
							return fixer.removeRange( [
								previousArg.range[ 1 ],
								range[ 1 ],
							] );
						};

						context.report( {
							node,
							messageId: 'unnecessaryDefault',
							fix: removeDefaultTextDomain,
						} );
						return;
					}

					const replaceTextDomain = ( fixer ) => {
						return fixer.replaceTextRange(
							// account for quotes.
							[ range[ 0 ] + 1, range[ 1 ] - 1 ],
							allowedTextDomains[ 0 ]
						);
					};

					context.report( {
						node,
						messageId: 'invalidValue',
						data: {
							textDomain: value,
						},
						fix: canFixTextDomain ? replaceTextDomain : null,
					} );
				}
			},
		};
	},
};
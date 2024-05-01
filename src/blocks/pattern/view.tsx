const resizeIframes = () => {
	const containers = document.getElementsByClassName( 'blockify-pattern' ) as HTMLCollectionOf<HTMLDivElement>;

	for ( let i = 0; i < containers.length; i++ ) {
		const container = containers[ i ];
		const iframe = container.getElementsByClassName( 'blockify-pattern-iframe' )[ 0 ] as HTMLDivElement;

		container.style.height = ( iframe.offsetHeight / 3 ) + 'px';
	}
};

document.addEventListener( 'DOMContentLoaded', resizeIframes );
window.addEventListener( 'resize', resizeIframes );

import type { SVGProps } from "react";

const PostIcon = (props: SVGProps<SVGSVGElement>) => {
	return (
		<svg
			xmlns="http://www.w3.org/2000/svg"
			width="24px"
			height="24px"
			data-name="Layer 1"
			viewBox="0 0 32 32"
			{...props}
		>
			<path d="M7 31h18a3 3 0 0 0 3-3V10.829a3.2 3.2 0 0 0-.879-2.122l-6.828-6.828A3.2 3.2 0 0 0 18.172 1H7a3 3 0 0 0-3 3v24a3 3 0 0 0 3 3M24.586 9H21a1 1 0 0 1-1-1V4.414zM6 4a1 1 0 0 1 1-1h11v5a3 3 0 0 0 3 3h5v17a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1z" />
		</svg>
	);
};

export default PostIcon;

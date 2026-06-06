import React from "react";
import PageIcon from "./icons/page";
import PostIcon from "./icons/post";

type Props = {
	droppable: boolean;
};

export const TypeIcon: React.FC<Props> = (props) => {
	if (props.droppable) {
		return <PageIcon />;
	}

	return <PostIcon />;
};

import React from "react";
import { Icon } from "@wordpress/components";
import { file, page } from "@wordpress/icons";

type Props = {
	droppable: boolean;
};

export const TypeIcon: React.FC<Props> = (props) => {
	if (props.droppable) {
		return <Icon icon={file} size={20} />;
	}

	return <Icon icon={page} size={20} />;
};

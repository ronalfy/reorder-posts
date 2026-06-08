import React, { useEffect } from "react";
import { useSelect } from "@wordpress/data";
import { Notice } from "@wordpress/components";
import { ReactSpinner1 } from "@mediaron/react-spinners";
import { __ } from "@wordpress/i18n";
import List from "./list";
import { initializeReorderStore, getBoundSelectors, STORE_NAME } from "./store";
import type { ReorderConfig } from "./types";

type Props = ReorderConfig;

const Reorder = (props: Props) => {
	const { hierarchical } = props;

	useEffect(() => {
		void initializeReorderStore(props);
	}, []);

	const { isLoading, error } = useSelect((selectStore) => {
		const store = getBoundSelectors(selectStore);
		return {
			isLoading: store.getIsInitialLoading(),
			error: store.getError(),
		};
	}, []);

	if (isLoading) {
		return (
			<div className="reorder-posts-loading">
				<h2>{__("Loading posts...", "metronet-reorder-posts")}</h2>
				<ReactSpinner1
					size={100}
					speedMultiplier={1.2}
				/>
			</div>
		);
	}

	return (
		<div className="reorder-posts-interface">
			{error && (
				<Notice
					status="error"
					isDismissible={false}
				>
					{error}
				</Notice>
			)}
			<List hierarchical={hierarchical} />
		</div>
	);
};

export default Reorder;

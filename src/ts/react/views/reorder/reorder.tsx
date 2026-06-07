import React, { useEffect, useState } from "react";
import apiFetch from "@wordpress/api-fetch";
import { ReactSpinner1 } from "@mediaron/react-spinners";
import { __ } from "@wordpress/i18n";
import List from "./list";
import { PostData } from "./types";

type Response = {
	posts: PostData[];
};

type Props = {
	postsPerPage: number;
	postType: string;
	nonce: string;
	hierarchical: boolean;
	postStatus: string[];
};

const Reorder = ({
	postsPerPage,
	postType,
	nonce,
	hierarchical,
	postStatus,
}: Props) => {
	const [posts, setPosts] = useState<PostData[]>([]);
	const [loading, setLoading] = useState(true);

	useEffect(() => {
		const getPosts = async () => {
			const response = await apiFetch<Response>({
				path: `/reorder-posts/v1/posts?post_type=${postType}&posts_per_page=${postsPerPage}&offset=0&order=ASC&nonce=${nonce}&hierarchical=${hierarchical}&post_status=${postStatus.join(
					","
				)}`,
				method: "GET",
			})
				.then((response) => {
					return response as Response;
				})
				.catch((error) => {
					console.error(error);
					return { posts: [] } as Response;
				})
				.finally(() => {
					setLoading(false);
				});
			setPosts(response.posts || []);
		};
		getPosts();
	}, []);
	const getLoading = () => {
		return (
			<div className="loading">
				<h2>{__("Loading posts...", "metronet-reorder-posts")}</h2>
				<ReactSpinner1
					size={100}
					speedMultiplier={1.2}
				/>
			</div>
		);
	};
	if (loading) {
		return getLoading();
	}
	return (
		<div>
			<h1>Reorder</h1>
			<List
				data={posts}
				hierarchical={hierarchical}
			/>
		</div>
	);
};

export default Reorder;

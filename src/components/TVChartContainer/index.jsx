import * as React from 'react';
import './index.css';
import Datafeed from './api/'


function getLanguageFromURL() {
	const regex = new RegExp('[\\?&]lang=([^&#]*)');
	const results = regex.exec(window.location.search);
	return results === null ? null : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

export class TVChartContainer extends React.PureComponent {
	constructor(props) {
		super(props);
		this.state = { widget: '', ready: false };
	}
	static defaultProps = {
		symbol: 'GBP/USD',
		interval: '1',
		containerId: 'tv_chart_container',
		libraryPath: '/charting_library/',
		chartsStorageUrl: 'https://saveload.tradingview.com',
		chartsStorageApiVersion: '1.1',
		clientId: 'tradingview.com',
		userId: 'public_user_id',
		fullscreen: false,
		autosize: true,
		studiesOverrides: {},
	};
	componentDidMount() {
		let fromDate = new Date(this.props.from);
		let now = Date.now();
		const timeframe = Math.round((now - fromDate) / (1000 * 60 * 60 * 24));
		const widgetOptions = {
			debug: false,
			symbol: this.props.symbol,
			datafeed: Datafeed,
			interval: this.props.interval,
			container_id: this.props.containerId,
			library_path: this.props.libraryPath,
			locale: getLanguageFromURL() || 'en',
			disabled_features: ['use_localstorage_for_settings'],
			enabled_features: ['study_templates'],
			charts_storage_url: this.props.chartsStorageUrl,
			charts_storage_api_version: this.props.chartsStorageApiVersion,
			client_id: this.props.clientId,
			user_id: this.props.userId,
			fullscreen: this.props.fullscreen,
			autosize: this.props.autosize,
			studies_overrides: this.props.studiesOverrides,
			overrides: {
				"mainSeriesProperties.showCountdown": true,
				"paneProperties.background": "#131722",
				"paneProperties.vertGridProperties.color": "#363c4e",
				"paneProperties.horzGridProperties.color": "#363c4e",
				"symbolWatermarkProperties.transparency": 90,
				"scalesProperties.textColor": "#AAA",
				"mainSeriesProperties.candleStyle.wickUpColor": '#336854',
				"mainSeriesProperties.candleStyle.wickDownColor": '#7f323f',
			},
			timeframe: timeframe+"D"

		};

		const widget = (window.tvWidget = new window.TradingView.widget(
			widgetOptions
		));
		this.setState({ widget });
		// widget. 
		widget.onChartReady(() => {
			this.setState({ ready: true });
			console.log("Chart has loaded!");
		});
	}

	componentDidUpdate() {
		if (this.state.widget && this.state.ready) {
			console.log(this.state.widget);
			let fromDate = new Date(this.props.from);
			let now = Date.now();
			const timeframe = Math.round((now - fromDate) / (1000 * 60 * 60 * 24));
			this.state.widget.setTimeFrame({ val: timeframe + "D", res: "1W" });
			this.state.widget.setSymbol(this.props.symbol, this.props.interval, () => { });
		}

		// const widgetOptions = {
		// 	debug: false,
		// 	symbol: this.props.symbol,
		// 	datafeed: Datafeed,
		// 	interval: this.props.interval,
		// 	container_id: this.props.containerId,
		// 	library_path: this.props.libraryPath,
		// 	locale: getLanguageFromURL() || 'en',
		// 	disabled_features: ['use_localstorage_for_settings'],
		// 	enabled_features: ['study_templates'],
		// 	charts_storage_url: this.props.chartsStorageUrl,
		// 	charts_storage_api_version: this.props.chartsStorageApiVersion,
		// 	client_id: this.props.clientId,
		// 	user_id: this.props.userId,
		// 	fullscreen: this.props.fullscreen,
		// 	autosize: this.props.autosize,
		// 	studies_overrides: this.props.studiesOverrides,
		// 	overrides: {
		// 		"mainSeriesProperties.showCountdown": true,
		// 		"paneProperties.background": "#131722",
		// 		"paneProperties.vertGridProperties.color": "#363c4e",
		// 		"paneProperties.horzGridProperties.color": "#363c4e",
		// 		"symbolWatermarkProperties.transparency": 90,
		// 		"scalesProperties.textColor": "#AAA",
		// 		"mainSeriesProperties.candleStyle.wickUpColor": '#336854',
		// 		"mainSeriesProperties.candleStyle.wickDownColor": '#7f323f',
		// 	}
		// };

		// const widget = (window.tvWidget = new window.TradingView.widget(
		// 	widgetOptions
		// ));

		// widget.onChartReady(() => {
		// 	console.log("Chart has loaded!");
		// });
	}
	render() {
		return (
			<div
				id={this.props.containerId}
				className={'TVChartContainer'}
			/>
		);
	}
}

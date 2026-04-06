FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

# 只使用官方NuGet源
RUN dotnet nuget add source https://api.nuget.org/v3/index.json -n nuget.org || true

COPY *.sln ./
COPY SmsPlatform.Domain/*.csproj ./SmsPlatform.Domain/
COPY SmsPlatform.Application/*.csproj ./SmsPlatform.Application/
COPY SmsPlatform.Infrastructure/*.csproj ./SmsPlatform.Infrastructure/
COPY SmsPlatform.Api/*.csproj ./SmsPlatform.Api/

# 恢复依赖（使用官方源）
RUN dotnet restore --source https://api.nuget.org/v3/index.json

COPY . .

RUN dotnet publish SmsPlatform.Api/SmsPlatform.Api.csproj -c Release -o /app/publish --no-restore

# 运行时镜像
FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS runtime
WORKDIR /app

RUN mkdir -p /app/logs

COPY --from=build /app/publish .

ENV ASPNETCORE_URLS=http://+:5000
ENV ASPNETCORE_ENVIRONMENT=Production
EXPOSE 5000

ENTRYPOINT ["dotnet", "SmsPlatform.Api.dll"]
